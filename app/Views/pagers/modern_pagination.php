<?php use CodeIgniter\Pager\PagerRenderer;

/**
 * Modern 2026 Enterprise Pagination Template
 *
 * @var PagerRenderer $pager
 */
$pager->setSurroundCount(2);
?>
<ul class="sw-pagination">
    <?php if ($pager->hasPrevious()) : ?>
        <li>
            <a href="<?= $pager->getFirst() ?>" aria-label="First" title="First Page">
                <i class="icon-first"></i>
            </a>
        </li>
        <li>
            <a href="<?= $pager->getPrevious() ?>" aria-label="Previous" title="Previous Page">
                <i class="icon-arrow-left7"></i> Previous
            </a>
        </li>
    <?php endif ?>

    <?php foreach ($pager->links() as $link) : ?>
        <li <?= $link['active'] ? 'class="active"' : '' ?>>
            <a href="<?= $link['uri'] ?>">
                <?= $link['title'] ?>
            </a>
        </li>
    <?php endforeach ?>

    <?php if ($pager->hasNext()) : ?>
        <li>
            <a href="<?= $pager->getNext() ?>" aria-label="Next" title="Next Page">
                Next <i class="icon-arrow-right7"></i>
            </a>
        </li>
        <li>
            <a href="<?= $pager->getLast() ?>" aria-label="Last" title="Last Page">
                <i class="icon-last"></i>
            </a>
        </li>
    <?php endif ?>
</ul>
