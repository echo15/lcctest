<?php

namespace Drupal\selectra\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class SelectraController.
 */
class SelectraController extends ControllerBase {

  /**
   * Build.
   *
   * @return string
   *   Return Exercise Programming Instructions page markup.
   */
  public function build() {
    $pdf_link = "/modules/custom/selectra/Selectra_Exercise.pdf";

    return [
      '#theme' => 'selectra',
      '#pdf_file' => $pdf_link,
    ];
  }

}
