<?php

namespace Livewire\Features\SupportTesting;

use Illuminate\Testing\TestResponse;
use Illuminate\Testing\TestView;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\ComponentHook;
use Livewire\Mechanisms\ComponentRegistry;
use PHPUnit\Framework\Assert;

class SupportTesting extends ComponentHook
{
    public static function provide()
    {
        if (! app()->environment('testing')) {
            return;
        }

        if (class_exists('Laravel\Dusk\Browser')) {
            DuskTestable::provide();
        }

        static::registerTestingMacros();
    }

    public function dehydrate($context)
    {
        $target = $this->component;

        $errors = $target->getErrorBag();

        if (! $errors->isEmpty()) {
            $this->storeSet('testing.errors', $errors);
        }
    }

    public function hydrate()
    {
        $this->storeSet('testing.validator', null);
    }

    public function exception($e, $stopPropagation)
    {
        if (! $e instanceof ValidationException) {
            return;
        }

        $this->storeSet('testing.validator', $e->validator);
    }

    protected static function registerTestingMacros()
    {
        // Usage: $this->assertSeeLivewire('counter');
        TestResponse::macro('assertSeeLivewire', function ($component) {
            if (is_subclass_of($component, Component::class)) {
                $component = app(ComponentRegistry::class)->getName($component);
            }
            $escapedComponentName = trim(htmlspecialchars(json_encode(['name' => $component])), '{}');

            Assert::assertStringContainsString(
                $escapedComponentName,
                $this->getContent(),
                'Cannot find Livewire component ['.$component.'] rendered on page.'
            );

            return $this;
        });

        // Usage: $this->assertDontSeeLivewire('counter');
        TestResponse::macro('assertDontSeeLivewire', function ($component) {
            if (is_subclass_of($component, Component::class)) {
                $component = app(ComponentRegistry::class)->getName($component);
            }
            $escapedComponentName = trim(htmlspecialchars(json_encode(['name' => $component])), '{}');

            Assert::assertStringNotContainsString(
                $escapedComponentName,
                $this->getContent(),
                'Found Livewire component ['.$component.'] rendered on page.'
            );

            return $this;
        });

        if (class_exists(TestView::class)) {
            TestView::macro('assertSeeLivewire', function ($component) {
                if (is_subclass_of($component, Component::class)) {
                    $component = app(ComponentRegistry::class)->getName($component);
                }
                $escapedComponentName = trim(htmlspecialchars(json_encode(['name' => $component])), '{}');

                Assert::assertStringContainsString(
                    $escapedComponentName,
                    $this->rendered,
                    'Cannot find Livewire component ['.$component.'] rendered on page.'
                );

                return $this;
            });

            TestView::macro('assertDontSeeLivewire', function ($component) {
                if (is_subclass_of($component, Component::class)) {
                    $component = app(ComponentRegistry::class)->getName($component);
                }
                $escapedComponentName = trim(htmlspecialchars(json_encode(['name' => $component])), '{}');

                Assert::assertStringNotContainsString(
                    $escapedComponentName,
                    $this->rendered,
                    'Found Livewire component ['.$component.'] rendered on page.'
                );

                return $this;
            });
        }
    }
}
