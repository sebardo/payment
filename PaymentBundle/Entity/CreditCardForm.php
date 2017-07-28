<?php
namespace PaymentBundle\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Date;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Collection;

class CreditCardForm
{

    public $plan;
    
    public $firstname;

    public $lastname;

    public $creditcardAlias;

    public $cardType;

    public $cardNo;

    public $expirationDate;

    public $CVV;

    public $ts;

    protected $validator;
       
    public function __construct($validator) {
        $this->validator = $validator;
    }
        
    /**
     * @Assert\Callback
     */
    public function checkExpirationDate(ExecutionContext $context)
    {
        $expirationDate = $this->expirationDate;

        if (is_null($expirationDate)) {
            $context->addViolationAt("expirationDate", "La fecha de vencimiento es invalida", array(), null);
        } else {
            $ts = $expirationDate->format('U');
            if ($ts<=time()) {
                $context->addViolationAt("expirationDate", "La fecha de vencimiento es invalida", array(), null);
            }
        }

    }

    /**
     * @Assert\Callback
     */
    public function constraintValidation(ExecutionContext $context)
    {
        $customNotBlankConstraint = new NotBlank();
        $customNotBlankConstraint->message = "Este valor no puede estar vacio";

        $customLengthConstraint = new Length(16);
        $customLengthConstraint->minMessage = "cardNoMinLength";
        $customLengthConstraint->maxMessage = "cardNoMaxLength";

        $collectionConstraint = new Collection(array(
            "firstname"=> array(
                                    $customNotBlankConstraint,
                                ),
            "lastname"=> array(
                                    $customNotBlankConstraint,
                                ),
            "cardNo" => array(
                                new Regex("/^\d+$/"),
                                $customNotBlankConstraint,
                                $customLengthConstraint,
                                ),
            "cardType"=> array(
                                $customNotBlankConstraint,
                                ),
            "CVV" => array  (
                                $customNotBlankConstraint,
                             ),

            ));

        /**
         * validateValue expects either a scalar value and its constraint or an array and a constraint Collection
         */
        $errors = $this->validator->validateValue(array(
            "firstname" => $this->firstname,
            "lastname" => $this->lastname,
            "cardNo" => $this->cardNo,
            "cardType" => $this->cardType,
            "CVV" => $this->CVV,

        ), $collectionConstraint);

        /**
         * Count is used as this is not an array but a ConstraintViolationList
         */
        if (count($errors) !== 0) {
            $path = $context->getPropertyPath();
            foreach ($errors as $error) {
               $string = str_replace('[', '', $error->getPropertyPath());
               $string = str_replace(']', '', $string);
               $propertyPath = $path . '.'.$string;
               $context->addViolationAt($string, $error->getMessage(), array(), null);
            }
        }
    }
    
}
