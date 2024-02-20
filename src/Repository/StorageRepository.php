<?php

namespace FelipeFrancesco\LaravelSQSLargePayload\Repository;

use Exception;
use FelipeFrancesco\LaravelSQSLargePayload\Exceptions\InvalidStorageException;
use FelipeFrancesco\LaravelSQSLargePayload\Exceptions\LaravelSQSLargePayloadException;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * @infection-ignore-all
 */
class StorageRepository {
    /**
     * The name of the storage in filesystem disks to store payload in case of payload larger than the maximum size.
     *
     * @var string
     */
    private $storage;

    /**
     * The prefix (or directory) in the storage bucket where to store payload files.
     *
     * @var string
     */
    private $storage_prefix;

    public function __construct($storage, $storage_prefix = "jobs") {
        $this->storage = $storage;
        $this->storage_prefix = $storage_prefix;
    }

    public function isValid() {
        return is_array(config("filesystems.disks.{$this->storage}"));
    }

    public function getDataFromStorage(string $id) : mixed
    {
        return Storage::disk($this->storage)->get("{$this->storage_prefix}/{$id}");
    }

    public function removeFromStorage(string $id) {
        try {
            Storage::disk($this->storage)->delete("{$this->storage_prefix}/{$id}");
        } catch(Throwable $e) {
            // Finds what to do here
        }
   }

    public function saveDataOnStorage(string $uuid, $data)
    {
        if($this->storage === "" || !is_array(config("filesystems.disks.{$this->storage}"))) {
            throw new InvalidStorageException("Storage not found.");
        }
        try {
            Storage::disk($this->storage)->put(
                "{$this->storage_prefix}/{$uuid}", json_encode($data, \JSON_UNESCAPED_UNICODE)
            );
        } catch(Exception $e) {
            throw new LaravelSQSLargePayloadException("Unable to save.", previous: $e);
        }
    }

}
