<?php

namespace App\Support;

class Permissions
{
    public const INVOICES_VIEW = 'invoices.view';

    public const INVOICES_CREATE = 'invoices.create';

    public const INVOICES_UPDATE = 'invoices.update';

    public const INVOICES_SEND = 'invoices.send';

    public const INVOICES_VOID = 'invoices.void';

    public const CLIENTS_VIEW = 'clients.view';

    public const CLIENTS_CREATE = 'clients.create';

    public const CLIENTS_UPDATE = 'clients.update';

    public const CLIENTS_DELETE = 'clients.delete';

    public const CLIENTS_INVITE = 'clients.invite';

    public const SERVICES_VIEW = 'services.view';

    public const SERVICES_CREATE = 'services.create';

    public const SERVICES_UPDATE = 'services.update';

    public const SERVICES_DELETE = 'services.delete';

    public const REPORTS_VIEW = 'reports.view';

    public const SETTINGS_MANAGE = 'settings.manage';

    public const USERS_MANAGE = 'users.manage';

    public const PORTAL_VIEW = 'portal.view';

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::INVOICES_VIEW => 'View invoices',
            self::INVOICES_CREATE => 'Create invoices',
            self::INVOICES_UPDATE => 'Edit invoices',
            self::INVOICES_SEND => 'Send invoices',
            self::INVOICES_VOID => 'Void invoices',
            self::CLIENTS_VIEW => 'View clients',
            self::CLIENTS_CREATE => 'Create clients',
            self::CLIENTS_UPDATE => 'Edit clients',
            self::CLIENTS_DELETE => 'Delete clients',
            self::CLIENTS_INVITE => 'Invite clients to portal',
            self::SERVICES_VIEW => 'View services',
            self::SERVICES_CREATE => 'Create services',
            self::SERVICES_UPDATE => 'Edit services',
            self::SERVICES_DELETE => 'Delete services',
            self::REPORTS_VIEW => 'View reports',
            self::SETTINGS_MANAGE => 'Manage settings',
            self::USERS_MANAGE => 'Manage users',
            self::PORTAL_VIEW => 'Access client portal',
        ];
    }

    /**
     * @return list<string>
     */
    public static function staffDefaults(): array
    {
        return [
            self::INVOICES_VIEW,
            self::INVOICES_CREATE,
            self::INVOICES_UPDATE,
            self::INVOICES_SEND,
            self::INVOICES_VOID,
            self::CLIENTS_VIEW,
            self::CLIENTS_CREATE,
            self::CLIENTS_UPDATE,
            self::CLIENTS_DELETE,
            self::CLIENTS_INVITE,
            self::SERVICES_VIEW,
            self::SERVICES_CREATE,
            self::SERVICES_UPDATE,
            self::SERVICES_DELETE,
            self::REPORTS_VIEW,
        ];
    }

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return array_keys(self::labels());
    }
}
