<?php

/**
 * Bootstrap 5 – Paginador completo con flechas < >
 * Autor: Isaac Ferrucho (AQI)
 */

$pager->setSurroundCount(2);
?>

<?php if ($pager->getPageCount() > 1): ?>
  <nav aria-label="<?= lang('Pager.pageNavigation') ?>">
    <ul class="pagination justify-content-end m-0">

      <!-- Primera página -->
      <?php if ($pager->getCurrentPageNumber() > 1): ?>
        <li class="page-item">
          <a class="page-link" href="<?= $pager->getFirst() ?>" aria-label="<?= lang('Pager.first') ?>">«</a>
        </li>
      <?php else: ?>
        <li class="page-item disabled"><span class="page-link">«</span></li>
      <?php endif; ?>

      <!-- Anterior -->
      <?php if ($pager->getPreviousPage()): ?>
        <li class="page-item">
          <a class="page-link" href="<?= $pager->getPreviousPage() ?>" aria-label="<?= lang('Pager.previous') ?>">&lt;</a>
        </li>
      <?php else: ?>
        <li class="page-item disabled"><span class="page-link">&lt;</span></li>
      <?php endif; ?>

      <!-- Páginas -->
      <?php foreach ($pager->links() as $link): ?>
        <li class="page-item <?= $link['active'] ? 'active' : '' ?>">
          <a class="page-link" href="<?= $link['uri'] ?>"><?= $link['title'] ?></a>
        </li>
      <?php endforeach; ?>

      <!-- Siguiente -->
      <?php if ($pager->getNextPage()): ?>
        <li class="page-item">
          <a class="page-link" href="<?= $pager->getNextPage() ?>" aria-label="<?= lang('Pager.next') ?>">&gt;</a>
        </li>
      <?php else: ?>
        <li class="page-item disabled"><span class="page-link">&gt;</span></li>
      <?php endif; ?>

      <!-- Última página -->
      <?php if ($pager->getCurrentPageNumber() < $pager->getPageCount()): ?>
        <li class="page-item">
          <a class="page-link" href="<?= $pager->getLast() ?>" aria-label="<?= lang('Pager.last') ?>">»</a>
        </li>
      <?php else: ?>
        <li class="page-item disabled"><span class="page-link">»</span></li>
      <?php endif; ?>

    </ul>
  </nav>
<?php endif; ?>