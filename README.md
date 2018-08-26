# PHP Phar Stream Wrapper

## Abstract & History

...

## Example

The following example is bundled within this package, the shown
`PharExtensionInterceptor` denies all stream wrapper invocations files
not having the `.phar` suffix. Interceptor logic has to be individual and
adjusted to according requirements.

```
\TYPO3\PharStreamWrapper\Manager::initialize(
    (new \TYPO3\PharStreamWrapper\Behavior())
        ->withAssertion(new \TYPO3\PharStreamWrapper\Interceptor\PharExtensionInterceptor())
);

if (in_array('phar', stream_get_wrappers())) {
    stream_wrapper_unregister('phar');
    stream_wrapper_register('phar', \TYPO3\PharStreamWrapper\PharStreamWrapper::class);
}
```

* `PharStreamWrapper` defined as class reference will be instantiated each time
  `phar://` streams shall be processed.
* `Manager` as singleton pattern being called by `PharStreamWrapper` instances
  in order to retrieve individual behavior and settings.
* `Behavior` holds reference to interceptor(s) that shall assert correct/allowed
  invocation of a given `$path` for a given `$command`. Interceptors implement
  the interface `Assertable`. Interceptors can act individually on following
  commands or handle all of them in case not defined specifically:  
  + `COMMAND_DIR_OPENDIR`
  + `COMMAND_MKDIR`
  + `COMMAND_RENAME`
  + `COMMAND_RMDIR`
  + `COMMAND_STEAM_METADATA`
  + `COMMAND_STREAM_OPEN`
  + `COMMAND_UNLINK`
  + `COMMAND_URL_STAT`

## Interceptor

```
class PharExtensionInterceptor implements Assertable
{
    /**
     * Determines whether the base file name has a ".phar" suffix.
     *
     * @param string $path
     * @param string $command
     * @return bool
     * @throws PharStreamWrapperException
     */
    public function assert(string $path, string $command): bool
    {
        if ($this->isValidPath($path)) {
            return true;
        }
        throw new Exception(
            sprintf(
                'Unexpected file extension in "%s"',
                $path
            ),
            1535198703
        );
    }

    /**
     * @param string $path
     * @return bool
     */
    private function isValidPath(string $path): bool
    {
        $baseFile = Helper::determineBaseFile($path);
        if ($baseFile === null) {
            return false;
        }
        $fileExtension = pathinfo($path, PATHINFO_EXTENSION);
        return strtolower($fileExtension) === 'phar';
    }
}
```

## Helper

* `Helper::determineBaseFile(string $path)`: Determines base file that can be
  accessed using the regular file system. For instance the following path
  `phar:///home/user/bundle.phar/content.txt` would be resolved to
  `/home/user/bundle.phar`.
* `Helper::resetOpCache()`: Resets PHP's OPcache if enabled as work-around for
  issues in `include()` or `require()` calls and OPcache delivering wrong
  results. More details can be found in PHP's bug tracker, for instance like
  (https://bugs.php.net/bug.php?id=66569)[https://bugs.php.net/bug.php?id=66569]
