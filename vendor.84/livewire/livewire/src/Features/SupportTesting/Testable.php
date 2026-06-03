<?php

namespace Livewire\Features\SupportTesting;

use BackedEnum;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Testing\TestResponse;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\Features\SupportEvents\TestsEvents;
use Livewire\Features\SupportFileDownloads\TestsFileDownloads;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Features\SupportFileUploads\FileUploadController;
use Livewire\Features\SupportRedirects\TestsRedirects;
use Livewire\Features\SupportValidation\TestsValidation;

/** @mixin TestResponse */
class Testable
{
    use Macroable { Macroable::__call as macroCall; }
    use MakesAssertions,
        TestsEvents,
        TestsFileDownloads,
        TestsRedirects,
        TestsValidation;

    protected function __construct(
        protected RequestBroker $requestBroker,
        protected ComponentState $lastState,
    ) {}

    /**
     * @param  string  $name
     * @param  array  $params
     * @param  array  $fromQueryString
     * @param  array  $cookies
     * @param  array  $headers
     * @return static
     */
    public static function create($name, $params = [], $fromQueryString = [], $cookies = [], $headers = [])
    {
        $name = static::normalizeAndRegisterComponentName($name);

        $requestBroker = new RequestBroker(app());

        $initialState = InitialRender::make(
            $requestBroker,
            $name,
            $params,
            $fromQueryString,
            $cookies,
            $headers,
        );

        return new static($requestBroker, $initialState);
    }

    /**
     * @param  string|array<string>|object  $name
     * @return string
     */
    public static function normalizeAndRegisterComponentName($name)
    {
        if (is_array($otherComponents = $name)) {
            $name = array_shift($otherComponents);

            foreach ($otherComponents as $key => $value) {
                if (is_numeric($key)) {
                    app('livewire')->isDiscoverable($name) || app('livewire')->component($value);
                } else {
                    app('livewire')->component($key, $value);
                }
            }
        } elseif (is_object($name)) {
            $anonymousClassComponent = $name;

            $name = str()->random(10);

            app('livewire')->component($name, $anonymousClassComponent);
        } else {
            app('livewire')->isDiscoverable($name) || app('livewire')->component($name);
        }

        return $name;
    }

    /**
     * @param  ?string  $driver
     * @return void
     */
    public static function actingAs(Authenticatable $user, $driver = null)
    {
        if (isset($user->wasRecentlyCreated) && $user->wasRecentlyCreated) {
            $user->wasRecentlyCreated = false;
        }

        auth()->guard($driver)->setUser($user);

        auth()->shouldUse($driver);
    }

    public function id()
    {
        return $this->lastState->getComponent()->getId();
    }

    /**
     * @param  string  $key
     */
    public function get($key)
    {
        return data_get($this->lastState->getComponent(), $key);
    }

    /**
     * @param  bool  $stripInitialData
     * @return string
     */
    public function html($stripInitialData = false)
    {
        return $this->lastState->getHtml($stripInitialData);
    }

    /**
     * @param  string  $name
     * @return $this
     */
    public function updateProperty($name, $value = null)
    {
        return $this->set($name, $value);
    }

    /**
     * @param  array  $values
     * @return $this
     */
    public function fill($values)
    {
        foreach ($values as $name => $value) {
            $this->set($name, $value);
        }

        return $this;
    }

    /**
     * @param  string  $name
     * @return $this
     */
    public function toggle($name)
    {
        return $this->set($name, ! $this->get($name));
    }

    /**
     * @param string|array<string mixed> $name
     * @return $this
     */
    public function set($name, $value = null)
    {
        if (is_array($name)) {
            foreach ($name as $key => $value) {
                $this->setProperty($key, $value);
            }
        } else {
            $this->setProperty($name, $value);
        }

        return $this;
    }

    /**
     * @param  string  $name
     * @return $this
     */
    public function setProperty($name, $value)
    {
        if ($value instanceof UploadedFile) {
            return $this->upload($name, [$value]);
        } elseif (is_array($value) && isset($value[0]) && $value[0] instanceof UploadedFile) {
            return $this->upload($name, $value, $isMultiple = true);
        } elseif ($value instanceof BackedEnum) {
            $value = $value->value;
        }

        return $this->update(updates: [$name => $value]);
    }

    /**
     * @param  string  $method
     * @return $this
     */
    public function runAction($method, ...$params)
    {
        return $this->call($method, ...$params);
    }

    /**
     * @param  string  $method
     * @return $this
     */
    public function call($method, ...$params)
    {
        if ($method === '$refresh') {
            return $this->commit();
        }

        if ($method === '$set') {
            return $this->set(...$params);
        }

        return $this->update(calls: [
            [
                'method' => $method,
                'params' => $params,
                'path' => '',
            ],
        ]);
    }

    /**
     * @return $this
     */
    public function commit()
    {
        return $this->update();
    }

    /**
     * @return $this
     */
    public function refresh()
    {
        return $this->update();
    }

    /**
     * @param  array  $calls
     * @param  array  $updates
     * @return $this
     */
    public function update($calls = [], $updates = [])
    {
        $newState = SubsequentRender::make(
            $this->requestBroker,
            $this->lastState,
            $calls,
            $updates,
            app('request')->cookies->all()
        );

        $this->lastState = $newState;

        return $this;
    }

    /**
     * @todo Move me outta here and into the file upload folder somehow...
     *
     * @param  string  $name
     * @param  array  $files
     * @param  bool  $isMultiple
     * @return $this
     */
    public function upload($name, $files, $isMultiple = false)
    {
        // This method simulates the calls Livewire's JavaScript
        // normally makes for file uploads.
        $this->call(
            '_startUpload',
            $name,
            collect($files)->map(function ($file) {
                return [
                    'name' => $file->name,
                    'size' => $file->getSize(),
                    'type' => $file->getMimeType(),
                ];
            })->toArray(),
            $isMultiple,
        );

        // This is where either the pre-signed S3 url or the regular Livewire signed
        // upload url would do its thing and return a hashed version of the uploaded
        // file in a tmp directory.
        $storage = FileUploadConfiguration::storage();
        try {
            $fileHashes = (new FileUploadController)->validateAndStore($files, FileUploadConfiguration::disk());
        } catch (ValidationException $e) {
            $this->call('_uploadErrored', $name, json_encode(['errors' => $e->errors()]), $isMultiple);

            return $this;
        }

        // We are going to encode the original file size, mimeType and hashName in the filename
        // so when we create a new TemporaryUploadedFile instance we can fake the
        // same file size, mimeType and hashName set for the original file upload.
        $newFileHashes = collect($files)->zip($fileHashes)->mapSpread(function ($file, $fileHash) {
            // MimeTypes contain slashes, so we replace them with underscores to ensure the filename is valid.
            $escapedMimeType = (string) str($file->getMimeType())->replace('/', '_');

            return (string) str($fileHash)->replaceFirst('.', "-hash={$file->hashName()}-mimeType={$escapedMimeType}-size={$file->getSize()}.");
        })->toArray();

        collect($fileHashes)->zip($newFileHashes)->mapSpread(function ($fileHash, $newFileHash) use ($storage) {
            $storage->move('/'.FileUploadConfiguration::path($fileHash), '/'.FileUploadConfiguration::path($newFileHash));
        });

        // Now we finish the upload with a final call to the Livewire component
        // with the temporarily uploaded file path.
        $this->call('_finishUpload', $name, $newFileHashes, $isMultiple);

        return $this;
    }

    /**
     * @param  string  $key
     */
    public function viewData($key)
    {
        return $this->lastState->getView()->getData()[$key];
    }

    public function getData()
    {
        return $this->lastState->getSnapshotData();
    }

    public function instance()
    {
        return $this->lastState->getComponent();
    }

    /**
     * @return Component
     */
    public function invade()
    {
        return \Livewire\invade($this->lastState->getComponent());
    }

    /**
     * @return $this
     */
    public function dump()
    {
        dump($this->lastState->getHtml());

        return $this;
    }

    /**
     * @return void
     */
    public function dd()
    {
        dd($this->lastState->getHtml());
    }

    /**
     * @return $this
     */
    public function tap($callback)
    {
        $callback($this);

        return $this;
    }

    /**
     * @param  string  $property
     */
    public function __get($property)
    {
        if ($property === 'effects') {
            return $this->lastState->getEffects();
        }
        if ($property === 'snapshot') {
            return $this->lastState->getSnapshot();
        }
        if ($property === 'target') {
            return $this->lastState->getComponent();
        }

        return $this->instance()->$property;
    }

    /**
     * @param  string  $method
     * @return $this
     */
    public function __call($method, $params)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $params);
        }

        $this->lastState->getResponse()->{$method}(...$params);

        return $this;
    }
}
