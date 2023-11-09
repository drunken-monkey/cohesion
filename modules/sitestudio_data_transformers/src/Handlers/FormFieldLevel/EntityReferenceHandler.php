<?php

namespace Drupal\sitestudio_data_transformers\Handlers\FormFieldLevel;

use Drupal\cohesion\LayoutCanvas\Element;
use Drupal\cohesion\LayoutCanvas\ElementModel;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;

/**
 * Handles Site Studio form fields of "entity-reference" type.
 */
class EntityReferenceHandler extends FieldHandlerBase implements FormFieldLevelHandlerInterface {

  /**
   * Site Studio Element type id.
   */
  const ID = 'form-entity-reference';
  const MAP = '/maps/field_level/entity-reference.map.yml';
  const SCHEMA = '/maps/field_level/entity-reference.schema.json';

  /**
   * @var UrlGeneratorInterface
   */
  protected $urlGenerator;

  protected $entityTypeManager;

  protected $resourceTypeRepository;

  /**
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $urlGenerator
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   */
  public function __construct(
    ModuleHandlerInterface $moduleHandler,
    UrlGeneratorInterface $urlGenerator,
    EntityTypeManagerInterface $entityTypeManager,
    ResourceTypeRepositoryInterface $resourceTypeRepository
  ) {
    parent::__construct($moduleHandler);
    $this->urlGenerator = $urlGenerator;
    $this->entityTypeManager = $entityTypeManager;
    $this->resourceTypeRepository = $resourceTypeRepository;
  }

  public function getData(Element $form_field, ElementModel $elementModel): array {
    $data = parent::getData($form_field, $elementModel);
    if (isset($data['data']['value']->entity_type, $data['data']['value']->entity)) {
      $entity_value = $data['data']['value'];
      $results = $this->entityTypeManager->getStorage($entity_value->entity_type)->loadByProperties(['uuid' => $entity_value->entity]);
      $entity = reset($results);
      if ($entity instanceof EntityInterface) {
        $resource_type = $this->resourceTypeRepository->get($entity_value->entity_type, $entity->bundle());
        $route_name = sprintf('jsonapi.%s.individual', $resource_type->getTypeName());
        $data['data']['value']->jsonapi_link = $this->urlGenerator->generateFromRoute($route_name, ['entity' => $entity_value->entity], ['absolute' => TRUE]);
      }
    }

    return $data;
  }
  /**
   * {@inheritdoc}
   */
  public function getSchema(Element $form_field = NULL): array {

    if (is_null($form_field)) {
      return json_decode($this->schema, TRUE);
    }

    $settings = $form_field->getModel()->getProperty('settings');
    if (isset($settings->schema) && !empty($settings->schema)) {
      $schema = json_decode($this->schema, TRUE);
      if (isset($settings->schema->maxLength)) {
        //@todo make this cleaner
        $schema['properties']['attributes']['properties']['value']['maxLength'] = $settings->schema->maxLength;
      }
    }

    return $schema;
  }

}
