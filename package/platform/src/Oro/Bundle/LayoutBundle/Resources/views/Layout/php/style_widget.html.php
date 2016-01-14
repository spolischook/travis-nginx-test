<?php //@codingStandardsIgnoreFile ?>
<?php if (!empty($attr['href'])): ?>
    <?php $attr['href'] = $view['assets']->getUrl($attr['href']); ?>
    <link rel="stylesheet" <?php echo $view['layout']->block($block, 'block_attributes', array('attr' => $attr)) ?>/>
<?php else: ?>
    <style <?php echo $view['layout']->block($block, 'block_attributes', array('attr' => $attr)) ?>>
        <?php echo $content ?>
    </style>
<?php endif ?>
