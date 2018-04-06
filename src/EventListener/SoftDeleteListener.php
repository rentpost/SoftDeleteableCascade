<?php

namespace FrankHouweling\SoftDeleteableCascade\EventListener;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use FrankHouweling\SoftDeleteableCascade\Exception\OnSoftDeleteUnknownTypeException;
use FrankHouweling\SoftDeleteableCascade\Mapping\Annotation\onSoftDelete;
use Gedmo\Mapping\Annotation\SoftDeleteable;
use Gedmo\Mapping\ExtensionMetadataFactory;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Soft delete listener class for onSoftDelete behaviour.
 */
class SoftDeleteListener implements EventSubscriber
{
    public static $ignore = [];

    public function getSubscribedEvents()
    {
        return [
            'preRemove'
        ];
    }

    public function preRemove(LifecycleEventArgs $args)
    {
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();
        $entity = $args->getEntity();

        $hash = crc32(json_encode([
            get_class($entity),
            $entity->getId()
        ]));

        if($this->isSoftdeleteableEntity(get_class($entity)) === false && !in_array($hash, static::$ignore))
        {
            return;
        }
        static::$ignore[] = $hash;
        $this->cascadeSoftDelete($em, $entity);
    }

    /**
     * @param EntityManager $entityManager
     * @param object $entity
     */
    public function cascadeSoftDelete(EntityManager $entityManager, $entity)
    {
        $className = get_class($entity);
        $annotationReader = new AnnotationReader();
        $reflectionClass = new \ReflectionClass(get_class($entity));
        $classMetadata = $entityManager->getClassMetadata($className);
        $associationMappings = $classMetadata->getAssociationMappings();
        foreach ($associationMappings as $association)
        {
            $fieldname = $association['fieldName'];
            $propertyAnnotations = $annotationReader->getPropertyAnnotations(
                $reflectionClass->getProperty($fieldname)
            );
            $softDeleteCascade = false;
            foreach($propertyAnnotations as $propertyAnnotation)
            {
                if($propertyAnnotation instanceof onSoftDelete)
                {
                    $softDeleteCascade = true;
                    break;
                }
            }
            if($softDeleteCascade === true)
            {
                $cascade = $entity->{'get' . ucfirst($fieldname)}();
                if($cascade instanceof Collection)
                {
                    $cascade->map(function($entity) use($entityManager)
                    {
                        $entityManager->remove($cascade);
                    }, $cascade);
                }
                else
                {
                    $entityManager->remove($cascade);
                }
            }
        }
    }

    /**
     * @param string $className
     * @return bool
     */
    public function isSoftdeleteableEntity(string $className): bool
    {
        $annotationReader = new AnnotationReader();
        $reflectionClass = new \ReflectionClass($className);
        $classAnnotations = $annotationReader->getClassAnnotations($reflectionClass);

        foreach ($classAnnotations as $classAnnotation)
        {
            if($classAnnotation instanceof SoftDeleteable)
            {
                return true;
            }
        }
        return false;
    }
}
