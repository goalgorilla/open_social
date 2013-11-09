<?php
/**
 * @file
 * Theme template for a social comments.
 *
 * Available variables:
 * - comments: A list of comments, each one contains:
 *   - id: The ID of the comment.
 *   - username: The name of user.
 *   - userphoto: The photo of user.
 *   - text: The text of the comment.
 *   - date: The date where comment was posted.

 * @see template_preprocess()
 * @see template_preprocess_social_comments_items()
 *
 * @ingroup themeable
 */
?>
<?php foreach($comments as $comment): ?>
  <article role="article" class="comment clearfix">
    <header class="comment-header">
      <div class="attribution">
        <article>
          <div class="item">
            <?php print $comment['userphoto']; ?>
          </div>
        </article>
        <div class="submitted">
          <p class="commenter-name">
            <span rel="schema:author">
              <?php print $comment['username']; ?>
            </span>
          </p>
          <p class="comment-time">
            <?php print $comment['date']; ?>
          </p>
        </div>
      </div>
    </header>
    <div class="comment-text">
      <div class="comment-arrow"></div>
      <div class="content">
        <div property="schema:text"><?php print $comment['text']; ?></div>
      </div>
    </div>
  </article>
<?php endforeach; ?>
