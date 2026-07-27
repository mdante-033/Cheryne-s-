<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Category;
use App\Models\MenuItem;
use App\Services\WhatsAppService;
use RuntimeException;

use function App\Helpers\clean_string;
use function App\Helpers\flash;
use function App\Helpers\json_response;
use function App\Helpers\rate_limit;
use function App\Helpers\view;

final class MenuController
{
    /**
     * Display the web-facing interactive menu with optional filters.
     */
    public function index(): void
    {
        $filters = $this->extractFilters();
        $items = [];
        $categories = [];

        try {
            $items = MenuItem::all($filters);
            $categories = Category::all();
        } catch (\Throwable $exception) {
            flash('danger', 'Unable to load the menu right now. Please try again later.');
        }

        view('menu', [
            'title'       => "Cheryne's Menu",
            'description' => "Browse Cheryne's menu for authentic local foods in Nyali, Mombasa. Call 0795 879797 to order.",
            'items'       => $items,
            'categories'  => $categories,
            'filters'     => $filters,
        ]);
    }

    /**
     * API endpoint returning filtered menu records as a secure JSON package.
     */
    public function apiIndex(): void
    {
        if (!rate_limit('api_menu', 60, 60)) {
            json_response(['success' => false, 'error' => 'Too many requests.'], 429);
        }

        json_response([
            'success' => true,
            'items'   => MenuItem::all($this->extractFilters())
        ]);
    }

    /**
     * View a single menu item profile.
     * * @param string $id
     */
    public function show(string $id): void
    {
        if (!is_numeric($id) || (int) $id <= 0) {
            $this->abort404();
        }

        $item = MenuItem::find((int) $id);
        
        if ($item === null) {
            $this->abort404();
        }

        view('menu-item', [
            'title'        => ($item['name'] ?? 'Item') . " - Cheryne's Menu",
            'description'  => clean_string($item['description'] ?? '', 155),
            'item'         => $item,
            'whatsappLink' => WhatsAppService::itemLink($item),
        ]);
    }

    /**
     * Parse, sanitize, and format user input filters safely.
     *
     * @return array<string, mixed>
     */
    private function extractFilters(): array
    {
        $rawQuery = filter_input(INPUT_GET, 'q', FILTER_DEFAULT) ?? '';
        $rawAvailable = filter_input(INPUT_GET, 'available', FILTER_DEFAULT) ?? '';

        return [
            'category_id' => filter_input(INPUT_GET, 'category_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null,
            'available'   => is_string($rawAvailable) ? trim($rawAvailable) : '',
            'min_price'   => filter_input(INPUT_GET, 'min_price', FILTER_VALIDATE_FLOAT, ['options' => ['min_range' => 0]]) ?: null,
            'max_price'   => filter_input(INPUT_GET, 'max_price', FILTER_VALIDATE_FLOAT, ['options' => ['min_range' => 0]]) ?: null,
            'q'           => clean_string(is_string($rawQuery) ? trim($rawQuery) : '', 80),
        ];
    }

    /**
     * Handle unmatched routing targets elegantly.
     */
    private function abort404(): void
    {
        http_response_code(404);
        view('errors/404', [
            'title'       => 'Menu item not found',
            'description' => 'That menu item is currently unavailable or has been removed.',
        ]);
        exit;
    }
}