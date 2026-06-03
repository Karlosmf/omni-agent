<?php

/*
 * This file is part of Psy Shell.
 *
 * (c) 2012-2026 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Psy\ExecutionLoop;

use Psy\Shell;

/**
 * Execution Loop Listener interface.
 */
interface Listener
{
    /**
     * Determines whether this listener should be active.
     */
    public static function isSupported(): bool;

    /**
     * Called once before the REPL session starts.
     */
    public function beforeRun(Shell $shell);

    /**
     * Called at the start of each loop.
     */
    public function beforeLoop(Shell $shell);

    /**
     * Called on user input.
     *
     * Return a new string to override or rewrite user input.
     *
     *
     * @return string|null User input override
     */
    public function onInput(Shell $shell, string $input);

    /**
     * Called before executing user code.
     *
     * Return a new string to override or rewrite user code.
     *
     * Note that this is run *after* the Code Cleaner, so if you return invalid
     * or unsafe PHP here, it'll be executed without any of the safety Code
     * Cleaner provides. This comes with the big kid warranty :)
     *
     *
     * @return string|null User code override
     */
    public function onExecute(Shell $shell, string $code);

    /**
     * Called at the end of each loop.
     */
    public function afterLoop(Shell $shell);

    /**
     * Called once after the REPL session ends.
     *
     * @param  int  $exitCode  Exit code from the execution loop
     */
    public function afterRun(Shell $shell, int $exitCode = 0);
}
