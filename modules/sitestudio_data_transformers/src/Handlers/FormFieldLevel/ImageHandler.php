<?php

namespace Drupal\sitestudio_data_transformers\Handlers\FormFieldLevel;

use Drupal\cohesion\LayoutCanvas\Element;
use Drupal\cohesion\LayoutCanvas\ElementModel;
use Drupal\Core\Url;

/**
 *
 */
class ImageHandler extends FieldHandlerBase implements FormFieldLevelHandlerInterface {
  /**
   * Site Studio Element type id.
   */
  const ID = 'form-image';
  const MAP = '/maps/field_level/image.map.yml';
  const SCHEMA = '/maps/field_level/image.schema.json';

  /**
   * Converts `_property` array to data array.
   *
   * @param array $item
   *   Array containing property type and path.
   *
   * @return mixed
   *   Value stored in the property.
   */
  protected function processProperty(array $item): mixed {
    $property = parent::processProperty($item);

    if (isset($item['_process_token'])) {
      $property = $this->processToken($property);
    }

    return $property;
  }

  protected function processToken(string $property) {
    $processedToken = \Drupal::service('cohesion_image_browser.update_manager')->decodeToken($property);
    $path = \Drupal::service('file_url_generator')->generateAbsoluteString($processedToken['path']);
    return Url::fromUri($path)->toString();
  }

  /**
   * {@inheritdoc}
   */
  public function id(): string {
    return self::ID;
  }

  /**
   * {@inheritdoc}
   */
  public function getData(Element $form_field, ElementModel $elementModel): array {
    $this->form_field = $form_field;
    $this->elementModel = $elementModel;
    return parent::getData($form_field, $elementModel);
  }

}
