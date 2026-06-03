<?php

/**
 * Whoops - php errors for cool kids
 *
 * @author Filipe Dobreira <http://github.com/filp>
 */

namespace Whoops\Handler;

use Whoops\Inspector\InspectorInterface;
use Whoops\RunInterface;

interface HandlerInterface
{
    /**
     * @return int|null A handler may return nothing, or a Handler::HANDLE_* constant
     */
    public function handle();

    /**
     * @return void
     */
    public function setRun(RunInterface $run);

    /**
     * @param  \Throwable  $exception
     * @return void
     */
    public function setException($exception);

    /**
     * @return void
     */
    public function setInspector(InspectorInterface $inspector);
}
