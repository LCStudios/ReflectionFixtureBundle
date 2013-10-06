<?php
/**
 * @author Robin Gloster <robin@loc-com.de>
 */

namespace LCStudios\ReflectionFixtureBundle\DataFixtures;


use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

abstract class AbstractReflectionFixture extends AbstractFixture
{

    /**
     * @var ObjectManager
     */
    protected $manager;

    public final function load(ObjectManager $manager)
    {
        if (!class_exists($this->getClassName())) {
            throw new \InvalidArgumentException(
                sprintf('Class %s does not exist!', $this->getClassName())
            );
        }

        $this->manager = $manager;

        $objs = array();
        foreach ($this->loadArray() as $refName => $props) {
            $objs[$refName] = $this->create($props);
        }

        $manager->flush();

        foreach ($objs as $refName => $user) {
            $this->addReference($refName, $user);
        }
    }

    /**
     * Creates an object of class given by getClassName
     *
     * @param $properties array Properties to set on Class, key is property name, value is property value
     * @return mixed
     * @throws \InvalidArgumentException
     */
    protected function create(array $properties) {

        $className = $this->getClassName();
        $obj = new $className();

        $properties = array_merge($this->getDefaultParams(), $properties);

        foreach ($properties as $propertyName => $value) {
            if (! property_exists($obj, $propertyName)) {
                throw new \InvalidArgumentException(
                    sprintf('Property %s in class %s does not exist!',
                        $propertyName,
                        $this->getClassName()
                    )
                );
            }

            $setterName = 'set' . ucfirst($propertyName);
            if (!method_exists($obj, $setterName)) {
                throw new \InvalidArgumentException(
                    sprintf('Setter %s in class %s does not exist!',
                        $setterName,
                        $this->getClassName()
                    )
                );
            }

            $obj->$setterName($value);
        }

        $obj = $this->modifyObject($obj);

        $this->manager->persist($obj);

        return $obj;
    }

    protected function modifyObject($obj)
    {
        return $obj;
    }

    /**
     * Set properties on all Objects, can be overridden in loadArray
     *
     * @return array Properties as associative array, key is property name, value is property value
     */
    public function getDefaultParams()
    {
        return array();
    }

    /**
     * Associative array, key is reference Name, value is associative array, key is parameter name, value is parameter value
     *
     * @return array
     */
    public abstract function loadArray();

    /**
     * Name of class to create fixtures from
     *
     * @return string
     */
    public abstract function getClassName();

}

