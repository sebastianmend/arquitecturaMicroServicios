# Contexto y Solución para el Punto 11: Search Service

Este documento detalla la arquitectura y los pasos necesarios para implementar el **Servicio de Búsqueda (Search Service)**, correspondiente al punto 11 de la lista de servicios.

## 1. Arquitectura del Servicio de Búsqueda
El servicio de búsqueda funcionará como un agregador inteligente que consume los servicios de Libros y Autores para ofrecer capacidades de búsqueda unificada.

- **Nombre del Servicio**: `LumenSearchApi`
- **Puerto**: `8013`
- **Rol**: Microservicio de Búsqueda y Agregación.
- **Dependencias**: Consume `Books Service`, `Authors Service`, `Categories Service` (si existe).

### Flujo de Datos
1. **Cliente** -> **Gateway** (`/search`)
2. **Gateway** -> **Search Service** (Port 8013)
3. **Search Service** -> Consulta en paralelo/secuencial a:
    - `Books Service` (para obtener libros)
    - `Authors Service` (para obtener autores)
4. **Search Service** -> Procesa, filtra, ordena y compagina los resultados.
5. **Search Service** -> Retorna respuesta unificada.
6. **Gateway** -> Retorna al Cliente.

---

## 2. Implementación en el API Gateway (`LumenGatewayApi`)

Para que el Gateway sepa enrutar peticiones al nuevo servicio, se deben modificar los siguientes archivos:

### A. Configuración de Servicios (`config/services.php`)
Agregar la definición del servicio de búsqueda para leer la URL y el secreto desde el entorno.

```php
// config/services.php
'search' => [
    'base_uri' => env('SEARCH_SERVICE_BASE_URL'),
    'secret'   => env('SEARCH_SERVICE_SECRET'),
],
```

### B. Variables de Entorno (`.env`)
Definir la URL base del nuevo servicio.

```ini
SEARCH_SERVICE_BASE_URL=http://localhost:8013
SEARCH_SERVICE_SECRET=mysecret
```

### C. Consumidor del Servicio (`app/Services/SearchService.php`)
Crear un servicio en el Gateway que utilice el trait `ConsumesExternalService`. Este servicio actuará como cliente HTTP hacia el microservicio de búsqueda.

```php
<?php

namespace App\Services;

use App\Traits\ConsumesExternalService;

class SearchService
{
    use ConsumesExternalService;

    public $baseUri;
    public $secret;

    public function __construct()
    {
        $this->baseUri = config('services.search.base_uri');
        $this->secret  = config('services.search.secret');
    }

    public function search($data)
    {
        return $this->performRequest('GET', '/search', $data);
    }
    
    // Implementar otros métodos para /search/books, /search/authors, etc.
}
```

### D. Controlador (`app/Http/Controllers/SearchController.php`)
El controlador expone los endpoints en el Gateway y delega al `SearchService`.

```php
<?php

namespace App\Http\Controllers;

use App\Services\SearchService;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    use ApiResponser;

    public $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    public function index(Request $request)
    {
        return $this->successResponse($this->searchService->search($request->all()));
    }
    
    // Métodos adicionales para books, authors, etc.
}
```

### E. Rutas (`routes/web.php`)
Registrar las rutas públicas en el Gateway.

```php
$router->group(['prefix' => 'search'], function () use ($router) {
    $router->get('/', 'SearchController@index');
    $router->get('/books', 'SearchController@searchBooks');
    $router->get('/authors', 'SearchController@searchAuthors');
});
```

---

## 3. Implementación del Microservicio (`LumenSearchApi`)

Este es el nuevo proyecto Lumen que correrá en el puerto **8013**.

### A. Estructura y Dependencias
Debe tener una estructura similar a `LumenBooksApi`.
- **Trait `ConsumesExternalService`**: Necesario para consumir Books y Authors.
- **Servicios Internos**: `BookService` y `AuthorService` dentro de `LumenSearchApi` para comunicarse con los microservicios hermanos.

### B. Servicios de Consumo
El `LumenSearchApi` no accede a la base de datos de libros directamente. Debe consumirlos vía HTTP.

```php
// app/Services/BookService.php (en LumenSearchApi)
public function getAllBooks() {
    return $this->performRequest('GET', '/books');
}
```

### C. Lógica de Búsqueda (`app/Http/Controllers/SearchController.php`)
Aquí reside la lógica de negocio "compleja" (Filtrado y Ordenamiento).

1.  **Recibir Query**: `q`, `sort`, `price_min`, etc.
2.  **Obtener Datos**:
    *   Llamar a `BookService->getAllBooks()`.
    *   Llamar a `AuthorService->getAllAuthors()`.
3.  **Procesar (Filtrado Avanzado)**:
    *   Iterar sobre las colecciones.
    *   Aplicar filtros (e.g., `promedio_rating >= rating_min`).
    *   Aplicar búsqueda de texto (full-text o `strpos` simple si es MVP).
4.  **Ordenar y Paginar**:
    *   Usar colecciones de Laravel (`sortBy`, `forPage`).
5.  **Cachear Results**:
    *   Usar `Cache::remember` para guardar búsquedas frecuentes por X minutos.

### D. Rutas (`routes/web.php`)
Endpoints internos del microservicio:

```php
$router->get('/search', 'SearchController@index');
$router->get('/search/books', 'SearchController@books');
$router->get('/search/authors', 'SearchController@authors');
```

## Resumen de Cambios Necesarios

1.  **Crear** carpeta `LumenSearchApi` (clonando un template de Lumen).
2.  **Copiar** `Trait/ConsumesExternalService` y `Trait/ApiResponser` a `LumenSearchApi`.
3.  **Configurar** `.env` en `LumenSearchApi` con los puertos de Books (8002) y Authors (8001).
4.  **Implementar Config** en `LumenGatewayApi` para apuntar a Search (8013).
5.  **Desarrollar Lógica** de agregación y filtro en el controlador de `LumenSearchApi`.
