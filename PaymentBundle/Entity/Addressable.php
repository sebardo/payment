<?php

namespace PaymentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use CoreBundle\Entity\Timestampable;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use CoreBundle\Entity\State;
use CoreBundle\Entity\Country;

/**
 * Addressable abstract class to define address fields
 *
 * @ORM\MappedSuperclass
 * 
 */
abstract class Addressable extends Timestampable
{
    // * @Assert\Callback(methods={"validateDni"})
    protected static $schedules = array(
        'schedule.empty' => 0,
        'schedule.anytime' => 1,
        'schedule.morning' => 2,
        'schedule.evening' => 3
    );

    /**
     * @var string
     *
     * @ORM\Column(name="dni", type="string", length=9, nullable=true)
     */
    protected $dni;

    /**
     * @var string
     *
     * @ORM\Column(name="address", type="string", length=255, nullable=true)
     */
    protected $address;

    /**
     * @var string
     *
     * @ORM\Column(name="postal_code", type="string", length=5, nullable=true)
     */
    protected $postalCode;

    /**
     * @var string
     *
     * @ORM\Column(name="city", type="string", length=100, nullable=true)
     */
    protected $city;

    /**
     * @var string
     *
     * @ORM\Column(name="state", type="string", length=100, nullable=true)
     */
    private $state;

    /**
     * @var Country
     *
     * @ORM\ManyToOne(targetEntity="CoreBundle\Entity\Country")
     * @ORM\JoinColumn(name="country_id", referencedColumnName="id", nullable=true, onDelete="set null")
     */
    private $country;

    /**
     * Get schedules list
     *
     * @return array
     */
    public static function getSchedules()
    {
        return self::$schedules;
    }

    /**
     * Set dni
     *
     * @param string $dni
     *
     * @return Address
     */
    public function setDni($dni)
    {
        $this->dni = $dni;

        return $this;
    }

    /**
     * Get dni
     *
     * @return string
     */
    public function getDni()
    {
        return $this->dni;
    }

    /**
     * Set address information
     *
     * @param Address $address
     */
    public function setAddressInfo(Address $address)
    {
        $this->setAddress($address->getAddress());
        $this->setPostalCode($address->getPostalCode());
        $this->setCity($address->getCity());
        if(!is_null($address->getState()))$this->setState($address->getState());
        $this->setCountry($address->getCountry());
    }

    /**
     * Get address information
     *
     * @return Address
     */
    public function getAddressInfo()
    {
        $address = new Address();

        $address->setAddress($this->getAddress());
        $address->setPostalCode($this->getPostalCode());
        $address->setCity($this->getCity());
        if(!is_null($this->getState()))$address->setState($this->getState());
        $address->setCountry($this->getCountry());

        return $address;
    }

    /**
     * Set address
     *
     * @param string $address
     *
     * @return Address
     */
    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set postal code
     *
     * @param string $postalCode
     *
     * @return Address
     */
    public function setPostalCode($postalCode)
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    /**
     * Get postal code
     *
     * @return string
     */
    public function getPostalCode()
    {
        return $this->postalCode;
    }

    /**
     * Set city
     *
     * @param string $city
     *
     * @return Address
     */
    public function setCity($city)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Get city
     *
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set state
     *
     * @param integer $state
     *
     * @return Actor
     */
    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * Get state
     *
     * @return integer
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Set country
     *
     * @param integer $country
     *
     * @return Actor
     */
    public function setCountry($country)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Get country
     *
     * @return integer
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Custom validator to check Dni
     *
     * @param ExecutionContextInterface $context
     */
    public function validateDni(ExecutionContextInterface $context)
    {
        $dni = $this->getDni();

        if(0 === preg_match('/^\d{8}[a-zA-Z]{1}$/', $dni) & 0 === preg_match('/^[XYZ]{1}\d{7}[a-zA-Z0-9]{1}$/', $dni) & 0 === preg_match('/^[ABCDEFGHJNPQRSUVW]{1}/', $dni)  & strlen($dni)>0){
            $context->addViolationAt('dni', 'Formato incorrecto');
            return;
        }

        //DNI & NIE
        if(preg_match('/^\d{8}[a-zA-Z]{1}$/', $dni) || preg_match('/^[XYZ]{1}\d{7}[a-zA-Z0-9]{1}$/', $dni)){
            //Posibles valores para la letra final
            $letters = array(
                0 => 'T', 1 => 'R', 2 => 'W', 3 => 'A', 4 => 'G', 5 => 'M',
                6 => 'Y', 7 => 'F', 8 => 'P', 9 => 'D', 10 => 'X', 11 => 'B',
                12 => 'N', 13 => 'J', 14 => 'Z', 15 => 'S', 16 => 'Q', 17 => 'V',
                18 => 'H', 19 => 'L', 20 => 'C', 21 => 'K',22 => 'E'
            );

            //Comprobar si es un DNI
            if (preg_match('/^\d{8}[a-zA-Z]{1}$/', $dni))
            {
                if (strtoupper($dni[strlen($dni) - 1]) != $letters[((int) substr($dni, 0, strlen($dni) - 1)) % 23]){
                    $context->addViolationAt('dni', 'dni.invalid.number');
                    return;
                }
            }
            //Comprobar si es un NIE
            else if (preg_match('/^[XYZ]{1}\d{7}[a-zA-Z0-9]{1}$/', $dni))
            {
                //Comprobar letra
                if (strtoupper($dni[strlen($dni) - 1]) !=$letters[((int) substr($dni, 1, strlen($dni) - 2)) % 23]){
                    $context->addViolationAt('dni', 'nie.invalid.number');
                    return;
                }
            }
        }
        //CIF
        if(preg_match('/^[ABCDEFGHJNPQRSUVW]{1}/', $dni)){
            $sum = 0;
            for ($i=2; $i<strlen($dni)-1; $i+=2) {
                $sum += substr($dni, $i, 1);
            }

            for ($i=1; $i<strlen($dni)-1; $i+=2) {
                $t = substr($dni, $i, 1) * 2;
                $sum += ($t>9)?($t-9):$t;
            }

            $control = 10 - ($sum % 10);

            if ( !(substr($dni, 8, 1) == $control || strtoupper(substr($dni, 8, 1)) == substr('JABCDEFGHI', $control, 1 ))){
                $context->addViolationAt('dni', 'cif.invalid');

                return;
            }
        }

    }
}