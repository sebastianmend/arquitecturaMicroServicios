<?php

namespace App\Services;

use App\Traits\ConsumesExternalService;

class SearchService
{
    use ConsumesExternalService;

    /**
     * The base uri to be used to consume the search service
     * @var string
     */
    public $baseUri;

    /**
     * The secret to be used to consume the search service
     * @var string
     */
    public $secret;

    public function __construct()
    {
        $this->baseUri = config('services.search.base_uri');
        $this->secret = config('services.search.secret');
    }

    /**
     * Search from the search service
     * @return string
     */
    public function search($data)
    {
        return $this->performRequest('GET', '/search', $data);
    }

    /**
     * Search books from the search service
     * @return string
     */
    public function searchBooks($data)
    {
        return $this->performRequest('GET', '/search/books', $data);
    }

    /**
     * Search authors from the search service
     * @return string
     */
    public function searchAuthors($data)
    {
        return $this->performRequest('GET', '/search/authors', $data);
    }
}
