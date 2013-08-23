<?php
/*
 * Copyright (c) 2013 Szurovecz János
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of
 * this software and associated documentation files (the "Software"), to deal in
 * the Software without restriction, including without limitation the rights to
 * use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies
 * of the Software, and to permit persons to whom the Software is furnished to do
 * so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace predaddy\eventhandling;

use Doctrine\Common\Annotations\Reader;
use ReflectionClass;
use ReflectionMethod;

/**
 * Finds handler methods which are annotated with Subscribe.
 *
 * @author Szurovecz János <szjani@szjani.hu>
 */
class AnnotatedEventHandlerDescriptor implements EventHandlerDescriptor
{
    private $handlerClass;
    private $reader;
    private $directHandlerMethodDescriptors = array();
    private $compatibleHandlerMethodsCache = array();

    /**
     * @var FunctionDescriptorFactory
     */
    private $functionDescriptorFactory;

    /**
     * @param ReflectionClass $handlerClass
     * @param Reader $reader
     * @param FunctionDescriptorFactory $functionDescriptorFactory
     */
    public function __construct(
        ReflectionClass $handlerClass,
        Reader $reader,
        FunctionDescriptorFactory $functionDescriptorFactory
    ) {
        $this->handlerClass = $handlerClass;
        $this->reader = $reader;
        $this->functionDescriptorFactory = $functionDescriptorFactory;
        $this->findHandlerMethods();
    }

    /**
     * @param ReflectionClass $eventClass
     * @return array of ReflectionMethod
     */
    public function getHandlerMethodsFor(ReflectionClass $eventClass)
    {
        $eventClassName = $eventClass->getName();
        if (!array_key_exists($eventClassName, $this->compatibleHandlerMethodsCache)) {
            $this->compatibleHandlerMethodsCache[$eventClassName] = $this->findCompatibleMethodsFor($eventClass);
        }
        return $this->compatibleHandlerMethodsCache[$eventClassName];
    }

    /**
     * Find all handler methods for a specific type of Event
     *
     * @param ReflectionClass $eventClass
     * @return array of ReflectionMethod
     */
    protected function findCompatibleMethodsFor(ReflectionClass $eventClass)
    {
        $result = array();
        foreach ($this->directHandlerMethodDescriptors as $handlerEventClass => $funcDescriptors) {
            $firstDesc = $funcDescriptors[0];
            /* @var $firstDesc FunctionDescriptor */
            if ($firstDesc->isHandlerFor($eventClass)) {
                foreach ($funcDescriptors as $fDesc) {
                    $result[] = $fDesc->getReflectionFunction();
                }
            }
        }
        return $result;
    }

    protected function findHandlerMethods()
    {
        /* @var $reflMethod ReflectionMethod */
        foreach ($this->handlerClass->getMethods() as $reflMethod) {
            if ($this->reader->getMethodAnnotation($reflMethod, __NAMESPACE__ . '\Subscribe') === null) {
                continue;
            }
            if (!$this->isVisible($reflMethod)) {
                continue;
            }
            $funcDescriptor = $this->functionDescriptorFactory->create($reflMethod);
            if (!$funcDescriptor->isValid()) {
                continue;
            }
            $reflMethod->setAccessible(true);
            $this->directHandlerMethodDescriptors[$funcDescriptor->getHandledEventClassName()][] = $funcDescriptor;
        }
    }

    protected function isVisible(ReflectionMethod $method)
    {
        return $method->isPublic();
    }
}
