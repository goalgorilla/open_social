<?php

declare(strict_types=1);

namespace Drupal\signed_file_upload\Controller;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\signed_file_upload\DataObject\UploadSession;
use Drupal\signed_file_upload\Enum\PatchResult;
use Drupal\signed_file_upload\Enum\UploadState;
use Drupal\signed_file_upload\Exception\OperationConflictException;
use Drupal\signed_file_upload\SignedUploadFileLifecycleInterface;
use Drupal\signed_file_upload\UploadSessionManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;

/**
 * API implementation for tus file upload.
 */
class TusUploadController extends ControllerBase {

  protected const string TUS_RESUMABLE = '1.0.0';

  public function __construct(
    protected readonly UploadSessionManagerInterface $uploadSessionManager,
    protected readonly SignedUploadFileLifecycleInterface $signedUploadFileLifecycle,
    protected readonly TimeInterface $time,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get(UploadSessionManagerInterface::class),
      $container->get(SignedUploadFileLifecycleInterface::class),
      $container->get(TimeInterface::class),
    );
  }

  /**
   * Single resource for HEAD, PATCH, DELETE on /api/tus/{token}.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request being served.
   * @param non-empty-string $token
   *   The validated token associated with this request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response for this tus request.
   */
  public function handle(Request $request, string $token): Response {
    $allowedMethods = ['HEAD', 'PATCH', 'DELETE'];
    $method = $request->getMethod();
    if (!in_array($method, $allowedMethods, TRUE)) {
      throw new MethodNotAllowedHttpException($allowedMethods);
    }

    // @todo this should use version compare.
    if ($request->headers->get('Tus-Resumable') !== '1.0.0') {
      throw new PreconditionFailedHttpException('Unsupported Tus-Resumable version.', NULL, 0, [
        'Tus-Resumable' => self::TUS_RESUMABLE,
        'Tus-Version' => '1.0.0',
        'Tus-Extension' => 'expiration,termination',
      ]);
    }

    $session = $this->uploadSessionManager->loadSession($token);
    if ($session === NULL) {
      throw new NotFoundHttpException('Not Found', NULL, 0, [
        'Tus-Resumable' => self::TUS_RESUMABLE,
        'Tus-Version' => '1.0.0',
        'Tus-Extension' => 'expiration,termination',
      ]);
    }

    if ($session->state !== UploadState::Uploading || $this->time->getRequestTime() >= $session->uploadExpiresAt->getTimestamp()) {
      throw new GoneHttpException('Gone', NULL, 0, [
        'Tus-Resumable' => self::TUS_RESUMABLE,
        'Tus-Version' => '1.0.0',
        'Tus-Extension' => 'expiration,termination',
      ]);
    }

    return match ($method) {
      'HEAD' => $this->response($session, Response::HTTP_NO_CONTENT),
      'PATCH' => $this->handlePatch($request, $session),
      'DELETE' => $this->handleDelete($request, $session),
    };
  }

  /**
   * Generate a response for tus with standardized headers.
   *
   * @param \Drupal\signed_file_upload\DataObject\UploadSession $session
   *   The upload session which contains offset and expiry info.
   * @param int $status
   *   The HTTP Status code to return.
   * @param string|null $content
   *   The body contents of the response.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The produced Symfony response.
   */
  protected function response(UploadSession $session, int $status, ?string $content = NULL): Response {
    $headers = [
      'Tus-Resumable' => self::TUS_RESUMABLE,
      'Tus-Version' => '1.0.0',
      'Tus-Extension' => 'expiration,termination',
      'Upload-Offset' => $session->offset,
      'Upload-Expires' => $session->uploadExpiresAt->setTimezone(new \DateTimeZone('UTC'))->format(\DateTimeInterface::RFC7231),
    ];
    if ($session->uploadLength !== NULL) {
      $headers['Upload-Length'] = $session->uploadLength;
    }
    else {
      $headers['Upload-Defer-Length'] = '1';
    }
    if ($session->constraints->maxBytes > 0) {
      $headers['Tus-Max-Size'] = $session->constraints->maxBytes;
    }

    return (new Response($content, $status, $headers))
      ->setCache(['no_store' => TRUE]);
  }

  /**
   * Handle a PATCH request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request to handle.
   * @param \Drupal\signed_file_upload\DataObject\UploadSession $session
   *   The loaded upload session.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response to the PATCH request.
   */
  protected function handlePatch(Request $request, UploadSession $session): Response {
    if (strtolower($request->headers->get('Content-Type') ?? '') !== 'application/offset+octet-stream') {
      return (new Response(
        "Resumable uploads require content type 'application/offset+octet-stream'.",
        Response::HTTP_UNSUPPORTED_MEDIA_TYPE,
        ['Tus-Resumable' => self::TUS_RESUMABLE],
      ))->setCache(['no_store' => TRUE]);
    }

    $upload_offset = $request->headers->get('Upload-Offset');
    if ($upload_offset === NULL) {
      return (new Response(
        "Missing 'Upload-Offset' header.",
        Response::HTTP_BAD_REQUEST,
        ['Tus-Resumable' => self::TUS_RESUMABLE],
      ))->setCache(['no_store' => TRUE]);
    }
    $upload_offset = (int) $upload_offset;
    $upload_length = $request->headers->get('Upload-Length');
    $upload_length = $upload_length !== NULL ? (int) $upload_length : NULL;

    // Open the request input as contents so that we can stream it into the
    // file.
    $body_stream = $request->getContent(TRUE);
    try {
      $operation = $this->signedUploadFileLifecycle->patchUpload(
        $session,
        $body_stream,
        $upload_offset,
        $upload_length,
      );
      $session = $operation->session;
    }
    catch (OperationConflictException) {
      return (new Response("Upload session in use", Response::HTTP_CONFLICT, [
        "Tus-Resumable" => self::TUS_RESUMABLE,
      ]))->setCache(["no_store" => TRUE]);
    }
    finally {
      fclose($body_stream);
    }

    return match ($operation->result) {
      PatchResult::Ok => $this->response($session, Response::HTTP_NO_CONTENT),
      PatchResult::UploadOffsetMismatch => $this->response($session, Response::HTTP_CONFLICT, "Incorrect offset value."),
      PatchResult::LengthExceeded => $this->response($session, Response::HTTP_CONFLICT),
      PatchResult::UploadLengthAlreadySet => $this->response($session, Response::HTTP_BAD_REQUEST, "Upload length was already set"),
      PatchResult::NegativeUploadLength => $this->response($session, Response::HTTP_BAD_REQUEST, "Upload length is invalid (must be 0 or greater)."),
      PatchResult::TooLargeUploadLength => $this->response($session, Response::HTTP_BAD_REQUEST, "Upload length is invalid (exceeds Tus-Max-Size)."),
      PatchResult::PassedUploadLength => $this->response($session, Response::HTTP_BAD_REQUEST, "Upload length is invalid (passed Upload-Offset)."),
      PatchResult::InvalidContentSignature => $this->response($session, Response::HTTP_UNPROCESSABLE_ENTITY, 'Uploaded bytes do not match the declared file format.'),
    };
  }

  /**
   * Handle a DELETE request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request information.
   * @param \Drupal\signed_file_upload\DataObject\UploadSession $session
   *   The loaded upload session.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response to the DELETE request.
   */
  protected function handleDelete(Request $request, UploadSession $session): Response {
    try {
      $this->signedUploadFileLifecycle->deleteUpload($session);

      return $this->response($session, Response::HTTP_NO_CONTENT);
    }
    catch (OperationConflictException) {
      return (new Response("Upload session in use", Response::HTTP_CONFLICT, [
        "Tus-Resumable" => self::TUS_RESUMABLE,
      ]))->setCache(["no_store" => TRUE]);
    }
  }

}
