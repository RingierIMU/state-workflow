<?php

namespace Ringierimu\StateWorkflow\Workflow;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\MarkingStore\MarkingStoreInterface;

/**
 * Class MethodMarkingStore.
 */
class MethodMarkingStore implements MarkingStoreInterface
{
    private $property;
    private $propertyAccessor;
    private $singleState;

    /**
     * MethodMarkingStore constructor.
     *
     * @param bool   $singleState
     * @param string $property    Used to determine methods to call
     *                            The `getMarking` method will use `$subject->getProperty()`
     *                            The `setMarking` method will use `$subject->setProperty(string|array $places, array $context = array())`
     */
    public function __construct(bool $singleState = false, string $property = 'marking')
    {
        $this->singleState = $singleState;
        $this->property = $property;
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
    }

    /**
     * {@inheritdoc}
     */
    public function getMarking(object $subject): Marking
    {
        $marking = $this->propertyAccessor->getValue($subject, $this->property);

        if (null === $marking) {
            return new Marking();
        }

        if ($this->singleState) {
            $marking = [(string) $marking => 1];
        }

        return new Marking($marking);
    }

    /**
     * {@inheritdoc}
     */
    public function setMarking(object $subject, Marking $marking, array $context = [])
    {
        $this->propertyAccessor->setValue($subject, $this->property, key($marking->getPlaces()));
    }
}
