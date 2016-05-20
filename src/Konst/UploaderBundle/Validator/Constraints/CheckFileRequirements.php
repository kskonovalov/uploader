<?php
/**
 * Created by PhpStorm.
 * User: konstantin
 * Date: 16.5.16
 * Time: 14.12
 */

namespace Konst\UploaderBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class CheckFileRequirements extends Constraint
{
    public $message = 'File doesn\'t meet the requirements.';
}