<?php

/**
 * RBAC staff bajo Admin (group = 1).
 * Jugador / PV / Operador no usan esta capa.
 */

if (! function_exists('bingo_permissions_catalog')) {
    /**
     * @return list<array{key:string,name:string,module:string}>
     */
    function bingo_permissions_catalog(): array
    {
        return [
            ['key' => 'games.view', 'name' => 'Ver juegos', 'module' => 'Juegos'],
            ['key' => 'games.manage', 'name' => 'Gestionar juegos / premios', 'module' => 'Juegos'],
            ['key' => 'users.view', 'name' => 'Ver usuarios', 'module' => 'Usuarios'],
            ['key' => 'users.manage', 'name' => 'Crear / editar usuarios', 'module' => 'Usuarios'],
            ['key' => 'users.wallets', 'name' => 'Ajustar saldos de usuarios', 'module' => 'Usuarios'],
            ['key' => 'stores.view', 'name' => 'Ver puntos de venta', 'module' => 'Puntos de venta'],
            ['key' => 'stores.manage', 'name' => 'Gestionar puntos de venta', 'module' => 'Puntos de venta'],
            ['key' => 'operators.view', 'name' => 'Ver operadores', 'module' => 'Operadores'],
            ['key' => 'operators.manage', 'name' => 'Gestionar operadores', 'module' => 'Operadores'],
            ['key' => 'commissions.settle', 'name' => 'Liquidar comisiones', 'module' => 'Comisiones'],
            ['key' => 'payments.view', 'name' => 'Ver pagos / billetera admin', 'module' => 'Pagos'],
            ['key' => 'payments.manage', 'name' => 'Aprobar depósitos / retiros / ajustes', 'module' => 'Pagos'],
            ['key' => 'kyc.review', 'name' => 'Revisar KYC', 'module' => 'KYC'],
            ['key' => 'low_balance.view', 'name' => 'Jugadores saldo bajo', 'module' => 'Soporte'],
            ['key' => 'stats.view', 'name' => 'Ver estadísticas', 'module' => 'Estadísticas'],
            ['key' => 'audit.view', 'name' => 'Auditoría financiera', 'module' => 'Estadísticas'],
            ['key' => 'ggr.view', 'name' => 'Ver GGR / afiliados', 'module' => 'GGR'],
            ['key' => 'ggr.manage', 'name' => 'Gestionar GGR', 'module' => 'GGR'],
            ['key' => 'legal.manage', 'name' => 'Contenido legal', 'module' => 'Legal'],
            ['key' => 'settings.manage', 'name' => 'Configuración del sistema', 'module' => 'Sistema', 'sensitive' => true],
            ['key' => 'notifications.manage', 'name' => 'Notificaciones push', 'module' => 'Marketing'],
            ['key' => 'email.manage', 'name' => 'Email marketing', 'module' => 'Marketing'],
            ['key' => 'packages.manage', 'name' => 'Paquetes', 'module' => 'Marketing'],
            ['key' => 'levels.manage', 'name' => 'Niveles / logros', 'module' => 'Marketing'],
            ['key' => 'admins.manage', 'name' => 'Crear staff y asignar permisos', 'module' => 'Administración', 'sensitive' => true],
        ];
    }
}

if (! function_exists('bingo_staff_roles_seed')) {
    /**
     * @return list<array{slug:string,name:string,description:string,is_superadmin:int,permissions:list<string>}>
     */
    function bingo_staff_roles_seed(): array
    {
        $all = array_column(bingo_permissions_catalog(), 'key');

        return [
            [
                'slug' => 'superadmin',
                'name' => 'Superadministrador',
                'description' => 'Acceso total al panel Admin',
                'is_superadmin' => 1,
                'permissions' => $all,
            ],
            [
                'slug' => 'support',
                'name' => 'Soporte',
                'description' => 'Usuarios, KYC y saldo bajo',
                'is_superadmin' => 0,
                'permissions' => [
                    'games.view',
                    'users.view',
                    'users.manage',
                    'kyc.review',
                    'low_balance.view',
                    'stats.view',
                ],
            ],
            [
                'slug' => 'finance',
                'name' => 'Finanzas',
                'description' => 'Pagos, comisiones, auditoría y GGR',
                'is_superadmin' => 0,
                'permissions' => [
                    'games.view',
                    'users.view',
                    'users.wallets',
                    'stores.view',
                    'stores.manage',
                    'operators.view',
                    'operators.manage',
                    'commissions.settle',
                    'payments.view',
                    'payments.manage',
                    'stats.view',
                    'audit.view',
                    'ggr.view',
                    'ggr.manage',
                ],
            ],
            [
                'slug' => 'operations',
                'name' => 'Operaciones',
                'description' => 'Juegos, tablero y operación diaria',
                'is_superadmin' => 0,
                'permissions' => [
                    'games.view',
                    'games.manage',
                    'users.view',
                    'stats.view',
                    'low_balance.view',
                ],
            ],
            [
                'slug' => 'marketing',
                'name' => 'Marketing',
                'description' => 'Campañas, notificaciones y contenido',
                'is_superadmin' => 0,
                'permissions' => [
                    'games.view',
                    'stats.view',
                    'legal.manage',
                    'notifications.manage',
                    'email.manage',
                    'packages.manage',
                    'levels.manage',
                ],
            ],
        ];
    }
}

if (! function_exists('bingo_ensure_permissions_schema')) {
    function bingo_ensure_permissions_schema(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        try {
            $db = \Config\Database::connect();
            $forge = \Config\Database::forge();

            if (! $db->tableExists('admin_roles')) {
                $forge->addField([
                    'id' => [
                        'type'           => 'INT',
                        'constraint'     => 11,
                        'unsigned'       => true,
                        'auto_increment' => true,
                    ],
                    'slug' => [
                        'type'       => 'VARCHAR',
                        'constraint' => 64,
                    ],
                    'name' => [
                        'type'       => 'VARCHAR',
                        'constraint' => 120,
                    ],
                    'description' => [
                        'type'       => 'VARCHAR',
                        'constraint' => 255,
                        'null'       => true,
                    ],
                    'is_superadmin' => [
                        'type'       => 'TINYINT',
                        'constraint' => 1,
                        'default'    => 0,
                    ],
                    'status' => [
                        'type'       => 'TINYINT',
                        'constraint' => 1,
                        'default'    => 1,
                    ],
                    'created_at' => [
                        'type' => 'DATETIME',
                        'null' => true,
                    ],
                    'updated_at' => [
                        'type' => 'DATETIME',
                        'null' => true,
                    ],
                ]);
                $forge->addKey('id', true);
                $forge->addUniqueKey('slug');
                $forge->createTable('admin_roles', true);
            }

            if (! $db->tableExists('admin_permissions')) {
                $forge->addField([
                    'id' => [
                        'type'           => 'INT',
                        'constraint'     => 11,
                        'unsigned'       => true,
                        'auto_increment' => true,
                    ],
                    'key' => [
                        'type'       => 'VARCHAR',
                        'constraint' => 80,
                    ],
                    'name' => [
                        'type'       => 'VARCHAR',
                        'constraint' => 120,
                    ],
                    'module' => [
                        'type'       => 'VARCHAR',
                        'constraint' => 80,
                        'null'       => true,
                    ],
                    'created_at' => [
                        'type' => 'DATETIME',
                        'null' => true,
                    ],
                ]);
                $forge->addKey('id', true);
                $forge->addUniqueKey('key');
                $forge->createTable('admin_permissions', true);
            }

            if (! $db->tableExists('admin_role_permissions')) {
                $forge->addField([
                    'role_id' => [
                        'type'       => 'INT',
                        'constraint' => 11,
                        'unsigned'   => true,
                    ],
                    'permission_id' => [
                        'type'       => 'INT',
                        'constraint' => 11,
                        'unsigned'   => true,
                    ],
                ]);
                $forge->addKey(['role_id', 'permission_id'], true);
                $forge->createTable('admin_role_permissions', true);
            }

            if (! $db->tableExists('admin_user_permissions')) {
                $forge->addField([
                    'user_id' => [
                        'type'       => 'INT',
                        'constraint' => 11,
                        'unsigned'   => true,
                    ],
                    'permission_key' => [
                        'type'       => 'VARCHAR',
                        'constraint' => 80,
                    ],
                ]);
                $forge->addKey(['user_id', 'permission_key'], true);
                $forge->addKey('user_id');
                $forge->createTable('admin_user_permissions', true);
            }

            if ($db->tableExists('users') && ! $db->fieldExists('admin_role_id', 'users')) {
                $forge->addColumn('users', [
                    'admin_role_id' => [
                        'type'       => 'INT',
                        'constraint' => 11,
                        'unsigned'   => true,
                        'null'       => true,
                        'default'    => null,
                        'after'      => 'group',
                    ],
                ]);
            }

            bingo_seed_admin_permissions();
        } catch (\Throwable $e) {
            log_message('error', 'bingo_ensure_permissions_schema: ' . $e->getMessage());
        }
    }
}

if (! function_exists('bingo_seed_admin_permissions')) {
    function bingo_seed_admin_permissions(): void
    {
        $db = \Config\Database::connect();
        if (! $db->tableExists('admin_roles') || ! $db->tableExists('admin_permissions')) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $permIds = [];

        foreach (bingo_permissions_catalog() as $perm) {
            $existing = $db->table('admin_permissions')->where('key', $perm['key'])->get()->getRowArray();
            if ($existing) {
                $permIds[$perm['key']] = (int) $existing['id'];
                continue;
            }
            $db->table('admin_permissions')->insert([
                'key'        => $perm['key'],
                'name'       => $perm['name'],
                'module'     => $perm['module'],
                'created_at' => $now,
            ]);
            $permIds[$perm['key']] = (int) $db->insertID();
        }

        foreach (bingo_staff_roles_seed() as $role) {
            $row = $db->table('admin_roles')->where('slug', $role['slug'])->get()->getRowArray();
            if (! $row) {
                $db->table('admin_roles')->insert([
                    'slug'          => $role['slug'],
                    'name'          => $role['name'],
                    'description'   => $role['description'],
                    'is_superadmin' => (int) $role['is_superadmin'],
                    'status'        => 1,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]);
                $roleId = (int) $db->insertID();
            } else {
                $roleId = (int) $row['id'];
                $db->table('admin_roles')->where('id', $roleId)->update([
                    'name'          => $role['name'],
                    'description'   => $role['description'],
                    'is_superadmin' => (int) $role['is_superadmin'],
                    'status'        => 1,
                    'updated_at'    => $now,
                ]);
            }

            $db->table('admin_role_permissions')->where('role_id', $roleId)->delete();
            foreach ($role['permissions'] as $pkey) {
                if (! isset($permIds[$pkey])) {
                    continue;
                }
                $db->table('admin_role_permissions')->insert([
                    'role_id'       => $roleId,
                    'permission_id' => $permIds[$pkey],
                ]);
            }
        }

        // Admins existentes sin rol → Superadministrador
        if ($db->tableExists('users') && $db->fieldExists('admin_role_id', 'users')) {
            $super = $db->table('admin_roles')->where('slug', 'superadmin')->get()->getRowArray();
            if ($super) {
                $db->table('users')
                    ->where('group', bingo_group_admin())
                    ->groupStart()
                        ->where('admin_role_id', null)
                        ->orWhere('admin_role_id', 0)
                    ->groupEnd()
                    ->update(['admin_role_id' => (int) $super['id']]);
            }
        }
    }
}

if (! function_exists('bingo_list_admin_roles')) {
    /**
     * @return list<array>
     */
    function bingo_list_admin_roles(bool $onlyAssignable = true): array
    {
        bingo_ensure_permissions_schema();
        $db = \Config\Database::connect();
        if (! $db->tableExists('admin_roles')) {
            return [];
        }

        $builder = $db->table('admin_roles')->where('status', 1)->orderBy('is_superadmin', 'DESC')->orderBy('name', 'ASC');
        if ($onlyAssignable && ! bingo_can('admins.manage')) {
            $builder->where('is_superadmin', 0);
        }

        return $builder->get()->getResultArray();
    }
}

if (! function_exists('bingo_list_admin_roles_with_permissions')) {
    /**
     * Roles con listado de permisos (clave + nombre) para la UI de alta.
     *
     * @return list<array>
     */
    function bingo_list_admin_roles_with_permissions(bool $onlyAssignable = true): array
    {
        $catalog = [];
        foreach (bingo_permissions_catalog() as $perm) {
            $catalog[$perm['key']] = $perm;
        }

        $roles = bingo_list_admin_roles($onlyAssignable);
        foreach ($roles as &$role) {
            $keys = ((int) ($role['is_superadmin'] ?? 0) === 1)
                ? array_keys($catalog)
                : bingo_fetch_role_permission_keys((int) ($role['id'] ?? 0));

            $items = [];
            foreach ($keys as $key) {
                if (! isset($catalog[$key])) {
                    continue;
                }
                $items[] = [
                    'key'    => $key,
                    'name'   => $catalog[$key]['name'],
                    'module' => $catalog[$key]['module'],
                ];
            }
            $role['permission_keys'] = $keys;
            $role['permissions'] = $items;
        }
        unset($role);

        return $roles;
    }
}

if (! function_exists('bingo_fetch_role_permission_keys')) {
    /**
     * @return list<string>
     */
    function bingo_fetch_role_permission_keys(int $roleId): array
    {
        if ($roleId <= 0) {
            return [];
        }

        bingo_ensure_permissions_schema();
        $db = \Config\Database::connect();
        if (! $db->tableExists('admin_role_permissions')) {
            return [];
        }

        $rows = $db->table('admin_role_permissions rp')
            ->select('p.key')
            ->join('admin_permissions p', 'p.id = rp.permission_id', 'inner')
            ->where('rp.role_id', $roleId)
            ->get()
            ->getResultArray();

        return array_values(array_unique(array_map(static fn ($r) => (string) ($r['key'] ?? ''), $rows)));
    }
}

if (! function_exists('bingo_fetch_user_admin_permission_keys')) {
    /**
     * @return list<string>
     */
    function bingo_fetch_user_admin_permission_keys(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        bingo_ensure_permissions_schema();
        $db = \Config\Database::connect();
        if (! $db->tableExists('admin_user_permissions')) {
            return [];
        }

        $rows = $db->table('admin_user_permissions')
            ->select('permission_key')
            ->where('user_id', $userId)
            ->get()
            ->getResultArray();

        return array_values(array_unique(array_filter(array_map(
            static fn ($r) => trim((string) ($r['permission_key'] ?? '')),
            $rows
        ))));
    }
}

if (! function_exists('bingo_save_user_admin_permissions')) {
    /**
     * @param list<string> $permissionKeys
     */
    function bingo_save_user_admin_permissions(int $userId, array $permissionKeys): void
    {
        if ($userId <= 0) {
            return;
        }

        bingo_ensure_permissions_schema();
        $db = \Config\Database::connect();
        if (! $db->tableExists('admin_user_permissions')) {
            return;
        }

        $allowed = array_column(bingo_permissions_catalog(), 'key');
        $clean = [];
        foreach ($permissionKeys as $key) {
            $key = trim((string) $key);
            if ($key !== '' && in_array($key, $allowed, true)) {
                $clean[$key] = $key;
            }
        }
        $clean = array_values($clean);

        $db->table('admin_user_permissions')->where('user_id', $userId)->delete();
        foreach ($clean as $key) {
            $db->table('admin_user_permissions')->insert([
                'user_id'        => $userId,
                'permission_key' => $key,
            ]);
        }
    }
}

if (! function_exists('bingo_staff_permission_presets')) {
    /**
     * Atajos de permisos para staff (no son el grupo del sistema).
     *
     * @return list<array{slug:string,name:string,description:string,permissions:list<string>}>
     */
    function bingo_staff_permission_presets(): array
    {
        $roles = bingo_staff_roles_seed();
        $out = [];
        foreach ($roles as $role) {
            if ((int) ($role['is_superadmin'] ?? 0) === 1 && (int) session()->get('admin_is_superadmin') !== 1) {
                continue;
            }
            $out[] = [
                'slug'        => (string) $role['slug'],
                'name'        => (string) $role['name'],
                'description' => (string) $role['description'],
                'permissions' => array_values($role['permissions'] ?? []),
            ];
        }

        return $out;
    }
}

if (! function_exists('bingo_assignable_permissions_catalog')) {
    /**
     * Permisos que el admin actual puede otorgar.
     *
     * @return list<array>
     */
    function bingo_assignable_permissions_catalog(): array
    {
        $canSensitive = (int) session()->get('admin_is_superadmin') === 1;
        $out = [];
        foreach (bingo_permissions_catalog() as $perm) {
            if (! empty($perm['sensitive']) && ! $canSensitive) {
                continue;
            }
            $out[] = $perm;
        }

        return $out;
    }
}

if (! function_exists('bingo_load_admin_authz_into_session')) {
    function bingo_load_admin_authz_into_session(?array $user = null): void
    {
        if ($user === null) {
            $userId = (int) session()->get('id');
            if ($userId <= 0) {
                return;
            }
            $user = (new \App\Models\UsersModel())->find($userId);
        }

        if (! $user || (int) ($user['group'] ?? -1) !== bingo_group_admin()) {
            session()->remove(['admin_role_id', 'admin_role_slug', 'admin_is_superadmin', 'admin_permissions']);

            return;
        }

        bingo_ensure_permissions_schema();
        $db = \Config\Database::connect();
        $userId = (int) ($user['id'] ?? 0);
        $roleId = (int) ($user['admin_role_id'] ?? 0);
        $role = null;

        if ($roleId > 0 && $db->tableExists('admin_roles')) {
            $role = $db->table('admin_roles')->where('id', $roleId)->where('status', 1)->get()->getRowArray();
        }

        $userPerms = bingo_fetch_user_admin_permission_keys($userId);
        $allKeys = array_column(bingo_permissions_catalog(), 'key');

        if ($userPerms !== []) {
            $permissions = $userPerms;
            $isSuper = count(array_diff($allKeys, $userPerms)) === 0;
            $slug = $isSuper ? 'superadmin' : 'custom';
        } elseif ($role && (int) ($role['is_superadmin'] ?? 0) === 1) {
            $permissions = $allKeys;
            $isSuper = true;
            $slug = (string) ($role['slug'] ?? 'superadmin');
        } elseif ($role) {
            $permissions = bingo_fetch_role_permission_keys((int) $role['id']);
            $isSuper = false;
            $slug = (string) ($role['slug'] ?? 'staff');
        } else {
            // Compatibilidad: admin sin permisos ni rol = superadmin
            $permissions = $allKeys;
            $isSuper = true;
            $slug = 'superadmin';
        }

        session()->set([
            'admin_role_id'       => $role ? (int) $role['id'] : 0,
            'admin_role_slug'     => $slug,
            'admin_is_superadmin' => $isSuper ? 1 : 0,
            'admin_permissions'   => $permissions,
        ]);
    }
}

if (! function_exists('bingo_can')) {
    function bingo_can(string $permission): bool
    {
        if (! session()->get('logged_in') || ! bingo_is_admin()) {
            return false;
        }

        if (session()->get('admin_permissions') === null) {
            bingo_load_admin_authz_into_session();
        }

        if ((int) session()->get('admin_is_superadmin') === 1) {
            return true;
        }

        $perms = session()->get('admin_permissions');
        if (! is_array($perms)) {
            return false;
        }

        return in_array($permission, $perms, true);
    }
}

if (! function_exists('bingo_can_any')) {
    /**
     * @param list<string> $permissions
     */
    function bingo_can_any(array $permissions): bool
    {
        foreach ($permissions as $p) {
            if (bingo_can($p)) {
                return true;
            }
        }

        return false;
    }
}

if (! function_exists('bingo_deny_permission_response')) {
    /**
     * @return \CodeIgniter\HTTP\ResponseInterface|\CodeIgniter\HTTP\RedirectResponse
     */
    function bingo_deny_permission_response(?string $message = null)
    {
        $message = $message ?: 'No tienes permiso para realizar esta acción.';
        $request = service('request');

        if ($request->isAJAX() || str_contains((string) $request->getHeaderLine('Accept'), 'application/json')) {
            return service('response')->setStatusCode(403)->setJSON([
                'success' => false,
                'message' => $message,
                'errors'  => ['permission' => $message],
            ]);
        }

        return redirect()->to(site_url('games'))->with('error', $message);
    }
}

if (! function_exists('bingo_require_admin_permission')) {
    /**
     * Exige admin + permiso. Devuelve null si OK, o Response/Redirect si deniega.
     *
     * @param string|list<string> $permission
     * @return \CodeIgniter\HTTP\ResponseInterface|\CodeIgniter\HTTP\RedirectResponse|null
     */
    function bingo_require_admin_permission($permission)
    {
        if (! session()->get('logged_in') || ! bingo_is_admin()) {
            $request = service('request');
            if ($request->isAJAX()) {
                return service('response')->setStatusCode(401)->setJSON([
                    'success' => false,
                    'message' => 'Sesión no válida',
                ]);
            }

            return redirect()->to('/signin');
        }

        $needed = is_array($permission) ? $permission : [$permission];
        if (bingo_can_any($needed)) {
            return null;
        }

        return bingo_deny_permission_response();
    }
}
