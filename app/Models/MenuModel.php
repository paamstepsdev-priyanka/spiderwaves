<?php

namespace App\Models;

use CodeIgniter\Model;

class MenuModel extends Model
{
    protected $table            = 'menus';
    protected $primaryKey       = 'menu_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'parent_id',
        'menu_code',
        'title',
        'url',
        'icon',
        'menu_type',
        'section_header',
        'badge_callback',
        'order_no',
        'module_key',
        'status',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Get menu by unique menu code.
     */
    public function getByCode(string $menuCode): ?array
    {
        return $this->where('menu_code', $menuCode)->first();
    }

    /**
     * Get all active menus ordered by section and order_no.
     */
    public function getActiveMenus(string $type = 'sidebar'): array
    {
        $types = ($type === 'sidebar') ? ['sidebar', 'both'] : ['navbar', 'both'];
        return $this->whereIn('menu_type', $types)
            ->where('status', '1')
            ->orderBy('order_no', 'ASC')
            ->findAll();
    }

    /**
     * Get all ancestor menus from root down to parent.
     */
    public function getAncestorChain(int $menuId): array
    {
        $chain = [];
        $currentId = $menuId;

        while ($currentId !== null && $currentId > 0) {
            $menu = $this->find($currentId);
            if (!$menu) {
                break;
            }
            $chain[] = $menu;
            $currentId = !empty($menu['parent_id']) ? (int)$menu['parent_id'] : null;
        }

        return array_reverse($chain);
    }
}

