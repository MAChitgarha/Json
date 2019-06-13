<?php
/**
 * JSON class file.
 *
 * @author Mohammad Amin Chitgarha <machitgarha@outlook.com>
 * @see https://github.com/MAChitgarha/JSON
 * @see https://packagist.org/packages/machitgarha/json
 */

namespace MAChitgarha\Component;

/**
 * Handles JSON data type.
 *
 * Gets a JSON string or a PHP native array or object and handles it as a JSON data.
 *
 * @see https://github.com/MAChitgarha/JSON/wiki
 * @see https://github.com/MAChitgarha/JSON/wiki/Glossary
 * @todo Import all methods from \ArrayObject.
 * @todo {@see https://stackoverflow.com/questions/29308898/how-do-i-extract-data-from-json-with-php}
 */
class JSON implements \ArrayAccess
{
    /** @var array Holds JSON data as a complete native PHP array (to be handled more easily). */
    protected $data;

    /**
     * @var int Default data type. Possible values: TYPE_JSON_STRING, TYPE_ARRAY, TYPE_OBJECT,
     * TYPE_SCALAR (class constants).
     */
    protected $defaultDataType = null;

    /** @var bool To decode every valid JSON string when setting values or not. */
    protected $jsonDecodeAlways = false;

    // Data types
    /** @var int The data type which you passed at creating new instance (i.e. constructor). */
    const TYPE_DEFAULT = 0;
    /** @var int JSON string data type. */
    const TYPE_JSON_STRING = 1;
    /** @var int Object data type (recursive), without converting indexed arrays to objects. */
    const TYPE_OBJECT = 2;
    /** @var int Array data type (recursive). */
    const TYPE_ARRAY = 3;
    /** @var int Object data type (recursive), with converting even indexed arrays to objects. */
    const TYPE_FULL_OBJECT = 4;
    /** @var int JSON class data type, i.e. a new instance of the class itself. */
    const TYPE_JSON_CLASS = 5;
    /** @var int Scalar data type, either an integer, string, float or boolean. */
    const TYPE_SCALAR = 6;

    // Options
    /**
     * @var int To decode every valid JSON string when setting values or not. This would be so much
     * useful if you're working with JSON strings a lot. The first thing that this option does is
     * that checks if a JSON string is a valid one and contains an object; and if it is, then
     * extract it to an array and make further operations, like setting the new value.
     */
    const JSON_DECODE_ALWAYS = 1;
    /**
     * @var int Consider data passed into the constructor as string, even if it's a valid JSON data;
     * in other words, don't decode it. This option has effect only in the constructor, and no other
     * methods will be affected by this.
     */
    const TREAT_AS_STRING = 2;

    /**
     * Prepares JSON data.
     *
     * @param mixed $data The data; can be either a countable value (i.e. a valid JSON string, array
     * or object) or a scalar type. Data should not contain any closures; otherwise, they will be
     * considered as empty objects.
     * @param int $options The additional options. Possible values: JSON::JSON_DECODE_ALWAYS,
     * JSON::TREAT_AS_STRING.
     * @throws \InvalidArgumentException If data is not either countable or scalar.
     */
    public function __construct($data = [], int $options = 0)
    {
        // Set options
        $this->jsonDecodeAlways = (bool)($options & self::JSON_DECODE_ALWAYS);
        $treatAsString = (bool)($options & self::TREAT_AS_STRING);

        if (($isObject = is_object($data)) || is_array($data)) {
            $this->defaultDataType = $isObject ? self::TYPE_OBJECT : self::TYPE_ARRAY;
            $this->data = self::convertToArray($data);
            return;
        }

        if (!$treatAsString && is_string($data) && self::isValidJson($data)) {
            $this->defaultDataType = self::TYPE_JSON_STRING;
            // This also checks for any JSON string errors
            $this->data = self::convertJsonToArray($data);
            return;
        }

        if (is_scalar($data)) {
            $this->defaultDataType = self::TYPE_SCALAR;
            $this->data = [$data];
            return;
        }

        // If data is invalid
        throw new \InvalidArgumentException("Data must be either countable or scalar");
    }

    /**
     * Checks a string data to be a valid JSON string.
     *
     * @param string $data Data to be validated.
     * @return array An array of two values:
     * [0]: Is the string a valid JSON or not,
     * [1]: The decoded JSON string, and it will be null if the string is not a valid JSON.
     */
    protected static function validateStringAsJson(string $data): array
    {
        json_decode($data);
        if (json_last_error() === JSON_ERROR_NONE) {
            return [true, $data];
        }
        return [false, $data];
    }

    /**
     * Checks if a string is a valid JSON or not.
     *
     * @param string $data Data to be checked.
     * @return bool
     */
    public static function isValidJson(string $data): bool
    {
        return self::validateStringAsJson($data)[0];
    }

    /**
     * Reads a valid JSON string, and if it is invalid, throws an exception.
     *
     * @param string $data String data to be read.
     * @return mixed A non-null value.
     * @throws \Exception When data is an invalid JSON string.
     */
    public static function readValidJson(string $data)
    {
        list($isValidJson, $decodedJson) = self::validateStringAsJson($data);
        if (!$isValidJson) {
            throw new \Exception("Invalid JSON string");
        }
        return $decodedJson;
    }

    /**
     * Converts a JSON string to an array.
     *
     * @param string $data Data as JSON string.
     * @return array
     * @throws \InvalidArgumentException If JSON string does not contain a data that could be
     * converted to an array.
     */
    protected static function convertJsonToArray(string $data): array
    {
        $decodedData = json_decode($data, true);
        if (!is_array($decodedData)) {
            throw new \InvalidArgumentException("Non-countable JSON string");
        }
        return $decodedData;
    }

    /**
     * Converts a JSON string to an object.
     *
     * @param string $data Data as JSON string.
     * @return object
     * @throws \InvalidArgumentException If the data cannot be converted to an object.
     */
    protected static function convertJsonToObject(string $data): object
    {
        $decodedData = json_decode($data);
        if (!is_object($decodedData)) {
            throw new \InvalidArgumentException("Non-countable JSON string");
        }
        return $decodedData;
    }

    /**
     * Converts countable data to JSON string.
     *
     * @param mixed $data A countable data, either an array or an object.
     * @return string
     * @throws \InvalidArgumentException If data is not countable.
     */
    protected function convertToJson($data): string
    {
        return json_encode($data);
    }

    /**
     * Converts an object to an array completely.
     * @param array|object $data Data as an array or an object.
     * @return array
     * @throws \InvalidArgumentException If data is not countable.
     */
    protected static function convertToArray($data): array
    {
        if (!(is_object($data) || is_array($data))) {
            throw new \InvalidArgumentException("Data must be either an array or an object");
        }

        return json_decode(json_encode($data), true);
    }

    /**
     * Converts an array or an object to an object recursively.
     *
     * @param array $data Data as array or object.
     * @param boolean $forceObject Whether to convert indexed arrays to objects or not.
     * @return object
     * @throws \InvalidArgumentException If data is not countable.
     */
    protected static function convertToObject($data, bool $forceObject = false): object
    {
        if (!(is_object($data) || is_array($data))) {
            throw new \InvalidArgumentException("Data must be either an array or an object");
        }

        return json_decode(json_encode($data, $forceObject ? JSON_FORCE_OBJECT : 0));
    }

    /**
     * Get the desirable value to be used elsewhere.
     * It will convert all countable values to full-indexed arrays. All other values than countable
     * values would be returned exactly the same.
     * Also, if JSON_DECODE_ALWAYS option is enabled, then it returns all
     *
     * @param mixed $value
     * @return mixed
     */
    protected function getOptimalValue($value)
    {
        if (is_array($value) || is_object($value)) {
            return self::convertToArray($value);
        }

        // JSON_DECODE_ALWAYS handler
        if ($this->jsonDecodeAlways && is_string($value)) {
            // Validating JSON string
            try {
                return self::convertJsonToArray($value);
            } catch (\Exception $e) {
            }
        }
        
        return $value;
    }

    /**
     * Returns data as the determined type.
     *
     * @param int $type Return type. Can be any of the JSON::TYPE_* constants, except
     * JSON::TYPE_JSON_CLASS.
     * @return string|array|object
     * @throws \InvalidArgumentException If the requested type is unknown.
     *
     * @since 0.3.1 Returns JSON if the passed data in constructor was a JSON string.
     */
    public function getData(int $type = self::TYPE_DEFAULT)
    {
        if ($type === self::TYPE_DEFAULT) {
            $type = $this->defaultDataType;
        }

        switch ($type) {
            case self::TYPE_JSON_STRING:
                return $this->getDataAsJsonString();
            case self::TYPE_ARRAY:
                return $this->getDataAsArray();
            case self::TYPE_OBJECT:
                return $this->getDataAsObject();
            case self::TYPE_FULL_OBJECT:
                return $this->getDataAsFullObject();
            default:
                throw new \InvalidArgumentException("Unknown data type");
        }
    }

    /**
     * Returns data as a JSON string.
     *
     * @param int $options The options, like JSON_PRETTY_PRINT. {@link
     * http://php.net/json.constants}
     * @return string
     */
    public function getDataAsJsonString(int $options = 0): string
    {
        if ($this->isDataScalar()) {
            return json_encode($this->data[0], $options);
        }
        return json_encode($this->data, $options);
    }

    /**
     * Returns data as an array.
     *
     * @return array The data as an array.
     */
    public function getDataAsArray(): array
    {
        return $this->data;
    }

    /**
     * Returns data as an object.
     *
     * @return object The data as an object.
     */
    public function getDataAsObject(): object
    {
        return self::convertToObject($this->data);
    }

    /**
     * Returns data as a full-converted object (i.e. even converts indexed arrays to objects).
     *
     * @return object
     */
    public function getDataAsFullObject(): object
    {
        return self::convertToObject($this->data, true);
    }

    /**
     * Follows keys to do (a) specific operation(s) with the element.
     * Crawl keys recursively, and find the requested element. Then, by using the closure, do a
     * specific set of operations with that element.
     * 
     * @param array $keys The keys to be crawled recursively.
     * @param array $data The data. It must be completely array (including its sub-elements), or
     * you may encounter errors.
     * @param callable $operation A set of operations to do with the element, as a closure. The
     * closure will get two arguments:
     * 1. The parent array of the element,
     * 2. The key to be accessed to that element using the parent array. 
     * The value returned by the closure will also be returned by this method.
     * @param boolean $strictIndexing To create keys as empty arrays and continue recursion if the
     * key cannot be found. For example, you can turn this on when you want to get an element's
     * value and you want to ensure that the element exists.
     * @return mixed Return the return value of the closure ($operation).
     * @throws \Exception When strict indexing is enable but a key does not exist.
     * @throws \Exception When a key doesn't contain an array (i.e. is non-countable) and cannot
     * continue crawling keys.
     */
    protected function crawlKeys(array $keys, array &$data, callable $operation, bool $strictIndexing = false)
    {
        // End of recursion
        if (count($keys) === 1) {
            $lastKey = $keys[0];
            if (!array_key_exists($lastKey, $data)) {
                if ($strictIndexing) {
                    throw new \Exception("The key '$lastKey' does not exist");
                } else {
                    $data[$lastKey] = null;
                }
            }
            return $operation($data, $lastKey);
        }
        // Crawl keys recursively
        else {
            // Get the current key, and remove it from keys array
            $currentKey = array_shift($keys);

            if (!array_key_exists($currentKey, $data)) {
                if ($strictIndexing) {
                    throw new \Exception("The key '$currentKey' does not exist");
                } else {
                    $data[$currentKey] = [];
                }
            } elseif (!is_array($data[$currentKey])) {
                throw new \Exception("The key '$currentKey' contains non-countable value");
            }

            // Recursion
            return $this->crawlKeys($keys, $data[$currentKey], $operation, $strictIndexing);
        }
    }

    /**
     * Extract keys from an index into an array by the delimiter.
     *
     * @param string $index The index.
     * @param string $delimiter The delimiter.
     * @return array The extracted keys.
     *
     * @since 0.3.2 Add escaping delimiters, i.e., using delimiters as the part of keys by escaping
     * them using a backslash.
     */
    protected function extractIndex(string $index, string $delimiter = "."): array
    {
        if ($index === "") {
            return [""];
        }

        $replacement = "¬";
        $escapedDelimiter = "\\$delimiter";

        // Replace the escaped delimiter with a less-using character
        $index = str_replace($escapedDelimiter, $replacement, $index);

        // Explode index parts by $delimiter
        $keys = explode($delimiter, $index);

        // Set the escaped delimiters
        foreach ($keys as &$key) {
            $key = str_replace($replacement, $delimiter, $key);
        }

        return $keys;
    }

    /**
     * Gets the value of an index in the data.
     *
     * @param string $index The index.
     * @return mixed The value of the index. Returns null if the index not found.
     */
    public function get(string $index)
    {
        try {
            return $this->crawlKeys($this->extractIndex($index), $this->data, function ($data, $key) {
                return $data[$key];
            }, true);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Sets the value to an index in the data.
     *
     * @param string $index The index.
     * @param mixed $value The value to be set.
     * @return self
     */
    public function set(string $index, $value): self
    {
        $value = $this->getOptimalValue($value);
        $this->crawlKeys($this->extractIndex($index), $this->data, function (&$data, $key) use ($value) {
            $data[$key] = $value;
        });
        return $this;
    }

    /**
     * Unset an index in the data.
     *
     * @param string $index The index
     * @return self
     */
    public function unset(string $index): self
    {
        $this->crawlKeys($this->extractIndex($index), $this->data, function (&$data, $key) {
            unset($data[$key]);
        }, true);
        return $this;
    }

    /**
     * Determines if an index exists or not.
     *
     * @param string $index The index.
     * @return bool Whether the index is set or not. A null value will be considered as not set.
     */
    public function isSet(string $index): bool
    {
        return $this->get($index) !== null;
    }
    
    /**
     * Iterates over an element.
     *
     * @param ?string $index The index.
     * @param int $returnType Specifies the value type in each iteration if the value is
     * countable. Can be of the JSON::TYPE_* constants.
     * @return \Generator
     * @throws \Exception If the value of the data index is not iterable (i.e. neither an array nor
     * an object).
     */
    public function iterate(string $index = null, int $returnType = self::TYPE_DEFAULT): \Generator
    {
        // Get the value of the index in data
        if (($data = $this->getCountable($index)) === null) {
            throw new \Exception("The index is not iterable");
        }

        if ($returnType === self::TYPE_DEFAULT) {
            $returnType = $this->defaultDataType;
        }

        // Define getValue function based on return type
        switch ($returnType) {
            case self::TYPE_JSON_STRING:
                $getValue = function ($val) {
                    return self::convertToJson($val);
                };
                break;

            case self::TYPE_ARRAY:
                $getValue = function ($val) {
                    return $val;
                };
                break;

            case self::TYPE_OBJECT:
                $getValue = function ($val) {
                    return self::convertToObject($val);
                };
                break;

            case self::TYPE_FULL_OBJECT:
                $getValue = function ($val) {
                    return self::convertToObject($val, true);
                };
                break;
            
            default:
                throw new \Exception("Unknown return type");
        }

        foreach ((array)($data) as $key => $val) {
            if (is_array($val)) {
                yield $key => $getValue($val);
            } else {
                yield $key => $val;
            }
        }
    }

    /**
     * Gets the JSON data string.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->getDataAsJsonString();
    }

    /**
     * Gets an element value, if it is countable.
     *
     * @param ?string $index The index. Pass null if you want to get the data itself.
     * @return array|null If the index is countable, returns it; otherwise, returns null.
     */
    protected function getCountable(string $index = null)
    {
        // Get the data
        if ($index === null) {
            return $this->data;
        }

        $value = $this->get($index);
        if (is_array($value)) {
            return $value;
        }
        return null;
    }

    /**
     * Determines whether an element is countable or not.
     *
     * @param string $index The index.
     * @return bool Is the index countable or not.
     */
    public function isCountable(string $index): bool
    {
        return $this->getCountable($index) !== null;
    }

    /**
     * Counts all elements in a countable element.
     *
     * @param ?string $index The index. Pass null if you want to get number of elements in the data.
     * @return int The elements number of the index.
     * @throws \Exception If the element is not countable.
     */
    public function count(string $index = null): int
    {
        // Get the number of keys in the specified index
        $countableValue = $this->getCountable($index);
        if ($countableValue === null) {
            throw new \Exception("The index is not countable");
        }
        return count($countableValue);
    }

    /**
     * Replaces data with a new data.
     *
     * @param array|object|string $data The new data to be replaced.
     * @return self
     */
    public function exchange($data): self
    {
        $this->__construct($data);
        return $this;
    }

    public function offsetExists($index): bool
    {
        return $this->isSet((string)($index));
    }

    public function offsetGet($index)
    {
        return $this->get((string)($index));
    }

    public function offsetSet($index, $value)
    {
        $this->set((string)($index), $value);
    }

    public function offsetUnset($index)
    {
        $this->unset((string)($index));
    }

    /**
     * Pushes a value to the end of a countable element in data.
     *
     * @param mixed $value The value to be inserted.
     * @param ?string $index The index of the countable element to be pushed into. Pass null if you
     * want to push to the data root.
     * @return self
     * @throws \Exception If the index is not countable (i.e. cannot push into it).
     */
    public function push($value, string $index = null): self
    {
        $value = $this->getOptimalValue($value);

        if ($index === null) {
            array_push($this->data, $value);
        } else {
            if (($arrayValue = $this->getCountable($index)) === null) {
                throw new \Exception("The index is not countable");
            }

            array_push($arrayValue, $value);

            $this->set($index, $arrayValue);
        }

        return $this;
    }

    /**
     * Pops the last value of a countable element from data.
     *
     * @param ?string $index The index of the countable element to be popped from. Pass null if you
     * want to pop from the data root.
     * @return self
     * @throws \Exception If the index is not countable (i.e. cannot pop from it).
     */
    public function pop(string $index = null): self
    {
        if ($index === null) {
            array_pop($this->data);
        } else {
            if (($arrayValue = $this->getCountable($index)) === null) {
                throw new \Exception("The index is not countable");
            }

            array_pop($arrayValue);

            $this->set($index, $arrayValue);
        }

        return $this;
    }

    /**
     * Tells whether data is scalar or not.
     *
     * @return bool
     */
    protected function isDataScalar(): bool
    {
        return $this->defaultDataType === self::TYPE_SCALAR;
    }
}
