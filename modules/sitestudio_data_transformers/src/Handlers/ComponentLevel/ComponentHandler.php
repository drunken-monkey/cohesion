<?php

namespace Drupal\sitestudio_data_transformers\Handlers\ComponentLevel;

use Drupal\cohesion\LayoutCanvas\Element;
use Drupal\cohesion\LayoutCanvas\LayoutCanvas;
use Drupal\cohesion_elements\Entity\Component;
use Drupal\sitestudio_data_transformers\Services\FormFieldManagerInterface;

/**
 * Handles Site Studio components data and schema.
 */
class ComponentHandler implements ComponentLevelHandlerInterface {

  /**
   * Site Studio Component "type".
   */
  const TYPE = 'component';

  const SCHEMA = '{"type": "object","properties": {"type": {"type": "string","pattern": "^component$"},"id": {"type" : "string"},"data": {"type": "object","properties":{"uid": {"type" : "string"},"title": {"type" : "string"},"field_data": {"type":"array"}}}},"required": ["id", "type", "data"]}';

  /**
   * Component regex pattern.
   */
  const PATTERN = '^component$';

  /**
   * Processed form fields uuids.
   *
   * @var array
   */
  protected $processedFields = [];

  /**
   * @var \Drupal\sitestudio_data_transformers\Services\FormFieldManagerInterface
   */
  protected $formFieldManager;

  /**
   * @param \Drupal\sitestudio_data_transformers\Services\FormFieldManagerInterface $formFieldManager
   */
  public function __construct(
    FormFieldManagerInterface $formFieldManager
  ) {
    $this->formFieldManager = $formFieldManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function type(): string {
    return self::TYPE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSchema() {
    // @todo Implement getSchema() method.
  }

  /**
   * {@inheritdoc}
   */
  public function hasChildren() {
    // @todo Implement hasChildren() method.
  }

  /**
   * {@inheritdoc}
   */
  public function getChildren() {
    // @todo Implement getChildren() method.
  }

  /**
   * {@inheritdoc}
   */
  public function getTransformedJson(Element $component) {
    $component_config = Component::load($component->getComponentID());
    $fields = [];
    $layoutCanvasInstance = $component_config->getLayoutCanvasInstance();

    if ($layoutCanvasInstance instanceof LayoutCanvas) {
      foreach ($layoutCanvasInstance->iterateComponentForm() as $form_field) {
        if ($layoutCanvasInstance !== $form_field->getParent()) {
          continue;
        }
        if ($form_field->getProperty('type') === 'form-container') {
          $containerFields = $this->processFormField($form_field, $component);
          $fields = array_merge(
            $fields,
            $containerFields
          );
          foreach ($form_field->getChildren() as $containerField) {
            $this->processedFields[] = $containerField->getUUID();
          }
        }
        elseif ($field = $this->processFormField($form_field, $component)) {
          $fields[] = $field;
        }
      }
    }

    $json = [
      'type' => self::TYPE,
      'id' => $component->getUUID(),
      'data' => [
        'uid' => $component->getComponentID(),
        'title' => $component->getProperty('title'),
        'field_data' => $fields,
      ],
    ];

    return $json;
  }

  protected function processFormField(Element $form_field, Element $component): array {
    $field = [];

    if (is_string($form_field->getProperty('uid')) && $this->formFieldManager->hasHandlerForType($form_field->getProperty('uid'))) {
      $field = $this->formFieldManager->getHandlerForType($form_field->getProperty('uid'))
        ->getData($form_field, $component->getModel());
    }
    if (!empty($field)) {
      $this->processedFields[] = $form_field->getUUID();
    }

    return $field;
  }

  /**
   * {@inheritdoc}
   */
  public function getStaticJsonSchema() {

    return (object) [
      'type' => 'object',
      'properties' => [
        'type' => [
          'type' => 'string',
          'pattern' => self::PATTERN,
        ],
        'id' => ['type' => 'string'],
        'data' => [
          'type' => 'object',
          'properties' => [
            'uid' => ['type' => 'string'],
            'title' => ['type' => 'string'],
            'data' => '$definitions/form_fields',
          ],
        ],
        'required' => ['id', 'type', 'data'],
      ],
    ];
  }

}
