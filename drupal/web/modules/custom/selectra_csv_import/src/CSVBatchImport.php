<?php

namespace Drupal\selectra_csv_import;

use Drupal\file\Entity\File;
use Drupal\selectra_test_two\Entity\ProductEntity;

/**
 * Class CSVBatchImport.
 *
 * @package Drupal\selectra_csv_import
 */
class CSVBatchImport {

  private $batch;

  private $fid;

  private $file;

  private $skip_first_line;

  private $delimiter;

  private $enclosure;

  /**
   * {@inheritdoc}
   */
  public function __construct($fid, $skip_first_line = FALSE, $delimiter = ';', $enclosure = ',', $batch_name = 'Selectra CSV import') {
    $this->fid = $fid;
    $this->file = File::load($fid);
    $this->skip_first_line = $skip_first_line;
    $this->delimiter = $delimiter;
    $this->enclosure = $enclosure;
    $this->batch = [
      'title' => $batch_name,
      'finished' => [$this, 'finished'],
      'file' => drupal_get_path('module', 'selectra_csv_import') . '/src/CSVBatchImport.php',
    ];
    $this->parseCSV();
  }

  /**
   * {@inheritdoc}
   */
  public function parseCSV() {
    if (($handle = fopen($this->file->getFileUri(), 'r')) !== FALSE) {
      if ($this->skip_first_line) {
        fgetcsv($handle, 0, ';');
      }
      while (($data = fgetcsv($handle, 0, ';')) !== FALSE) {
        $this->setOperation($data);
      }
      fclose($handle);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setOperation($data) {
    $this->batch['operations'][] = [[$this, 'processItem'], $data];
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($name, $imported, $type, $price, &$context) {
    $product = ProductEntity::create([
      'uid' => 1,
      'status' => 1,
    ]);
    $product->setName($name);
    $product->setImported($imported);
    $product->setProductType($type);
    $product->setPrice($price);
    $product->save();
    $context['results'][] =  $product->getName()  . ' Imported';
    $context['message'] = $product->getName() . ' Imported';
  }

  /**
   * {@inheritdoc}
   */
  public function setBatch() {
    batch_set($this->batch);
  }

  /**
   * {@inheritdoc}
   */
  public function processBatch() {
    batch_process();
  }

  /**
   * {@inheritdoc}
   */
  public function finished($success, $results, $operations) {
    if ($success) {
      $message = \Drupal::translation()
        ->formatPlural(count($results), 'One customer imported.', '@count customers imported.');
    }
    else {
      $message = t('Finished with an error.');
    }
    \Drupal::logger('selectra_csv_import')->notice($message);
  }

}