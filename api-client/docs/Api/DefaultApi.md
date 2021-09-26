# OpenAPI\Client\DefaultApi

All URIs are relative to http://localhost.

Method | HTTP request | Description
------------- | ------------- | -------------
[**getFeed()**](DefaultApi.md#getFeed) | **GET** /api/v1/get-feed | 


## `getFeed()`

```php
getFeed($user_id, $count)
```



### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');



$apiInstance = new OpenAPI\Client\Api\DefaultApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$user_id = 135; // string | ID пользователя
$count = 5; // string | Количество твитов в ленте

try {
    $apiInstance->getFeed($user_id, $count);
} catch (Exception $e) {
    echo 'Exception when calling DefaultApi->getFeed: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **user_id** | **string**| ID пользователя | [optional] [default to &#39;null&#39;]
 **count** | **string**| Количество твитов в ленте | [optional] [default to &#39;null&#39;]

### Return type

void (empty response body)

### Authorization

No authorization required

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: Not defined

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
