<?php

namespace App\Http\Controllers;

use App\Services\SearchService;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SearchController extends Controller
{
    use ApiResponser;

    /**
     * The service to consume the search service
     * @var SearchService
     */
    public $searchService;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * Search across books and authors
     * @return Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return $this->successResponse($this->searchService->search($request->all()));
    }

    /**
     * Search books
     * @return Illuminate\Http\Response
     */
    public function searchBooks(Request $request)
    {
        return $this->successResponse($this->searchService->searchBooks($request->all()));
    }

    /**
     * Search authors
     * @return Illuminate\Http\Response
     */
    public function searchAuthors(Request $request)
    {
        return $this->successResponse($this->searchService->searchAuthors($request->all()));
    }
}
