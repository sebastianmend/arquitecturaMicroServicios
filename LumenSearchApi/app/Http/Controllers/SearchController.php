<?php

namespace App\Http\Controllers;

use App\Services\BookService;
use App\Services\AuthorService;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SearchController extends Controller
{
    use ApiResponser;

    /**
     * The service to consume the books service
     * @var BookService
     */
    public $bookService;

    /**
     * The service to consume the authors service
     * @var AuthorService
     */
    public $authorService;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(BookService $bookService, AuthorService $authorService)
    {
        $this->bookService = $bookService;
        $this->authorService = $authorService;
    }

    /**
     * Search across books and authors
     * @return Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Obtain data from services
        $books = $this->bookService->obtainBooks();
        $authors = $this->authorService->obtainAuthors();

        // Decode if string (though trait usually returns array/mixed)
        if (is_string($books)) {
            $books = json_decode($books, true);
        }
        if (is_string($authors)) {
            $authors = json_decode($authors, true);
        }

        // Ensure we have collections
        $books = collect($books);
        $authors = collect($authors);

        // Filter Books
        if ($request->has('q')) {
            $books = $books->filter(function ($item) use ($request) {
                return false !== stristr($item['title'], $request->q) ||
                    false !== stristr($item['description'], $request->q);
            });

            $authors = $authors->filter(function ($item) use ($request) {
                return false !== stristr($item['name'], $request->q);
            });
        }

        // Combine results for a general search or return specifically if requested
        $results = [
            'books' => $books->values(),
            'authors' => $authors->values(),
        ];

        return $this->successResponse($results);
    }

    /**
     * Search only books
     */
    public function books(Request $request)
    {
        $books = $this->bookService->obtainBooks();
        if (is_string($books)) {
            $books = json_decode($books, true);
        }
        $books = collect($books);

        if ($request->has('q')) {
            $books = $books->filter(function ($item) use ($request) {
                return false !== stristr($item['title'], $request->q) ||
                    false !== stristr($item['description'], $request->q);
            });
        }

        if ($request->has('sort')) {
            $books = $books->sortBy->{$request->sort};
        }

        return $this->successResponse($books->values());
    }

    /**
     * Search only authors
     */
    public function authors(Request $request)
    {
        $authors = $this->authorService->obtainAuthors();
        if (is_string($authors)) {
            $authors = json_decode($authors, true);
        }
        $authors = collect($authors);

        if ($request->has('q')) {
            $authors = $authors->filter(function ($item) use ($request) {
                return false !== stristr($item['name'], $request->q);
            });
        }

        if ($request->has('sort')) {
            $authors = $authors->sortBy->{$request->sort};
        }

        return $this->successResponse($authors->values());
    }
}
