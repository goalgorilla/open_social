# Image optimization

This module offers the capability to optimize images via dynamic arguments.
GraphQL is a good example where this module would fit well, where images can
be requested on the fly in a specific size and format.

Image bucketing is applied on ratio and size level, so that the amount of image
variations are limited. For example if you request an image with a width of
95px, you will get an image back with a width of 100px (this is the nearest
bucket size).

### How to use

1. Generate private/public keys in the `../keys` dir. If you want to store the
the keys in a different directory, you will need to change
the `image_optmization.settings` config.
```
openssl genrsa -out private_key.pem 1024
openssl rsa -in private_key.pem -outform PEM -pubout -out public_key.pem
```
2. Generate image URL
```
$file = File::load(1);
$uuid = $file->uuid();
$image = $this->imageFactory->get($file->getFileUri());
$url = \Drupal::service('image_optimization.image_url_generator')->generate($uuid, $image, 50);
```
2. Visit generated image URL
