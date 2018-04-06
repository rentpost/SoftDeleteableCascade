<?php

namespace FrankHouweling\SoftDeleteableCascade\Mapping\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * onSoftDelete annotation for onSoftDelete behavioral extension.
 * @Annotation
 * @Target("PROPERTY")
 */
final class onSoftDelete extends Annotation
{
    /** @var string @Required */
    public $type;
}
