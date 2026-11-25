<?php use CodeIgniter\Pager\PagerRenderer;

/**
 * Limitless theme pagination template
 *
 * @var PagerRenderer $pager
 */
$pager->setSurroundCount(2);
?>
<ul class="pagination pagination-sm m-t-none m-b-none">
    <?php if ($pager->hasPrevious()) : ?>
        <li>
            <a href="<?= $pager->getFirst() ?>" aria-label="First">
                <span aria-hidden="true">First</span>
            </a>
        </li>
        <li>
            <a href="<?= $pager->getPrevious() ?>" aria-label="Previous">
                <span aria-hidden="true">&lsaquo;</span>
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
            <a href="<?= $pager->getNext() ?>" aria-label="Next">
                <span aria-hidden="true">&rsaquo;</span>
            </a>
        </li>
        <li>
            <a href="<?= $pager->getLast() ?>" aria-label="Last">
                <span aria-hidden="true">Last</span>
            </a>
        </li>
    <?php endif ?>
</ul>
