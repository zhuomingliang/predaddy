<?php
/*
 * Copyright (c) 2012-2014 Szurovecz János
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

namespace predaddy\messagehandling\interceptors;

use predaddy\messagehandling\DispatchInterceptor;
use predaddy\messagehandling\InterceptorChain;

/**
 * @package predaddy\messagehandling\interceptors
 *
 * @author Szurovecz János <szjani@szjani.hu>
 */
class BlockerInterceptor implements DispatchInterceptor
{
    private $blocking = false;

    /**
     * @var InterceptorChain[]
     */
    private $chains = [];

    /**
     * @var BlockerInterceptorManager
     */
    private $manager;

    public function __construct()
    {
        $this->manager = new BlockerInterceptorManager($this);
    }

    public function invoke($message, InterceptorChain $chain)
    {
        if ($this->blocking) {
            $this->chains[] = $chain;
        } else {
            $chain->proceed();
        }
    }

    public function startBlocking()
    {
        $this->blocking = true;
    }

    public function stopBlocking()
    {
        $this->blocking = false;
    }

    public function flush()
    {
        $chains = $this->chains;
        $this->clear();
        foreach ($chains as $chain) {
            $chain->proceed();
        }
    }

    public function clear()
    {
        $this->chains = [];
    }

    /**
     * @return BlockerInterceptorManager
     */
    public function manager()
    {
        return $this->manager;
    }
}