<?php

namespace Konst\UploaderBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * @Annotation
 */
class CheckFileRequirementsValidator extends ConstraintValidator
{
    public function validate($file, Constraint $constraint)
    {
        //nothing to do here for now
        return;
    }
}