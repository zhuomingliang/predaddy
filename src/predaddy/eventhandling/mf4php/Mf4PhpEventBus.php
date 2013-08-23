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

namespace predaddy\eventhandling\mf4php;

use mf4php\DefaultQueue;
use mf4php\Message;
use mf4php\MessageDispatcher;
use mf4php\MessageListener;
use predaddy\eventhandling\AbstractEventBus;
use predaddy\eventhandling\Event;
use predaddy\eventhandling\EventHandlerDescriptorFactory;
use predaddy\eventhandling\FunctionDescriptorFactory;

/**
 * EventBus implementation which uses mf4php to forward events.
 *
 * If you use a proper MessageDispatcher it is possible to
 * handle events after the transaction has been committed.
 *
 * With an asynchronous MessageDispatcher implementation event
 * handling can be asynchronous.
 *
 * Do not register it to the given MessageDispatcher,
 * it is handled by default.
 *
 * @author Szurovecz János <szjani@szjani.hu>
 */
class Mf4PhpEventBus extends AbstractEventBus implements MessageListener
{
    private $dispatcher;
    private $queue;
    private $objectMessageFactories = array();
    private $defaultObjectMessageFactory;

    public function __construct(
        $busId,
        MessageDispatcher $dispatcher,
        EventHandlerDescriptorFactory $handlerDescriptorFactory = null,
        FunctionDescriptorFactory $functionDescriptorFactory = null
    ) {
        parent::__construct($busId, $handlerDescriptorFactory, $functionDescriptorFactory);
        $this->dispatcher = $dispatcher;
        $this->queue = new DefaultQueue($busId);
        $this->defaultObjectMessageFactory = new DefaultObjectMessageFactory();
        $dispatcher->addListener($this->queue, $this);
    }

    /**
     * Only full matching class names are used, you should not register
     * a factory for an abstract event class/interface!
     *
     * If there is no registered factory for a particular event class,
     * DefaultObjectMessageFactory will be used.
     *
     * @param \predaddy\eventhandling\mf4php\ObjectMessageFactory $factory
     */
    public function registerObjectMessageFactory(ObjectMessageFactory $factory, $eventClass)
    {
        $this->objectMessageFactories[$eventClass] = $factory;
    }

    /**
     * Forward incoming event to handlers.
     *
     * @param Message $message
     */
    public function onMessage(Message $message)
    {
        $this->forwardEvent($message->getObject());
    }

    /**
     * Finds the appropriate message factory for the given event.
     *
     * @param Event $event
     * @return ObjectMessageFactory
     */
    protected function findObjectMessageFactory(Event $event)
    {
        $eventClass = $event->getClassName();
        foreach ($this->objectMessageFactories as $class => $factory) {
            if ($class === $eventClass) {
                return $factory;
            }
        }
        return $this->defaultObjectMessageFactory;
    }

    /**
     * Send the event to the message queue.
     *
     * @param Event $event
     */
    protected function innerPost(Event $event)
    {
        $message = $this->findObjectMessageFactory($event)->createMessage($event);
        $this->dispatcher->send($this->queue, $message);
    }
}
