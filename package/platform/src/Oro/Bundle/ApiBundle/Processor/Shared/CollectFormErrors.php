<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolation;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;

/**
 * Collects errors occurred during the the form submit and adds them into the Context.
 */
class CollectFormErrors implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var $context FormContext */

        if (!$context->hasForm()) {
            // no form
            return;
        }

        $form = $context->getForm();
        if (!$form->isSubmitted()) {
            // the form is not submitted
            return;
        }
        if ($form->isValid()) {
            // the form does not have errors
            return;
        }

        // collect form global errors
        $errors = $form->getErrors();
        foreach ($errors as $error) {
            $errorObject = $this->createErrorObject(
                $error,
                $this->getFormErrorPropertyPath($error, $form)
            );
            $context->addError($errorObject);
        }

        // collect form childes errors
        /** @var FormInterface $child */
        foreach ($form as $child) {
            if (!$child->isValid()) {
                foreach ($child->getErrors() as $error) {
                    $errorObject = $this->createErrorObject(
                        $error,
                        $child->getName()
                    );
                    $context->addError($errorObject);
                }
            }
        }
    }

    /**
     * @param FormError     $error
     * @param FormInterface $form
     *
     * @return string|null
     */
    protected function getFormErrorPropertyPath(FormError $error, FormInterface $form)
    {
        $result = null;

        $cause = $error->getCause();
        if ($cause instanceof ConstraintViolation) {
            $result = $cause->getPropertyPath();
            if (0 === strpos($result, 'data.')) {
                $result = substr($result, 5);
            }
            // in case if propertyPath = 'data', this error can be an entity level error
            if ($result === 'data' && !$form->has('data')) {
                $result = null;
            }
        }
        if (!$result) {
            $originName = $error->getOrigin()->getName();
            if ($originName) {
                $result = $originName;
            }
        }

        return $result;
    }

    /**
     * @param FormError   $formError
     * @param string|null $propertyPath
     *
     * @return Error
     */
    protected function createErrorObject(FormError $formError, $propertyPath = null)
    {
        $error = new Error();

        $error->setStatusCode(Response::HTTP_BAD_REQUEST);

        $cause = $formError->getCause();
        if ($cause instanceof ConstraintViolation) {
            if ($this->isExtraFieldsConstraint($cause)) {
                // special case "extra fields" constraint
                // see comments of "isExtraFieldsConstraint" method for more details
                $error->setTitle('extra fields constraint');
            } else {
                $constraint = $cause->getConstraint();
                $error->setTitle(ValueNormalizerUtil::humanizeClassName(get_class($constraint), 'Constraint'));
            }
        } else {
            // undefined constraint type
            $error->setTitle('form constraint');
        }

        $error->setDetail($formError->getMessage());

        if ($propertyPath) {
            $errorSource = new ErrorSource();
            $errorSource->setPropertyPath($propertyPath);
            $error->setSource($errorSource);
        }

        return $error;
    }

    /**
     * Checks whether a given cause of a form error represents "extra fields" constraint.
     * We have to do this because this type of validation does not have own validator
     * and a validation is performed by main form validator.
     * @see \Symfony\Component\Form\Extension\Validator\Constraints\FormValidator::validate
     *
     * @param ConstraintViolation $cause
     *
     * @return bool
     */
    protected function isExtraFieldsConstraint(ConstraintViolation $cause)
    {
        $parameters = $cause->getParameters();

        return array_key_exists('{{ extra_fields }}', $parameters) && 1 === count($parameters);
    }
}
