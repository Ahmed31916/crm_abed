import { PageCrudWrapper } from '@/components/PageCrudWrapper';
import { rolesConfig } from '@/config/crud/roles';
import { RolePermissionCheckboxGroup } from '@/components/RolePermissionCheckboxGroup';
import { PermissionBadges } from '@/components/PermissionBadges';
import { useEffect, useState } from 'react';
import { usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { toast } from 'sonner';
import { isDisabledEditRole } from '@/utils/helper';

function LabelInput({ formData, onChange, isDisabled, isViewMode }: { formData: any; onChange: any; isDisabled: boolean; isViewMode: boolean }) {
    return (
        isViewMode ?
            <div className="p-2 border rounded-md bg-gray-50">
                {formData.label}
            </div> :
            <input
                type="text"
                className={`flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 ${(isDisabled || isViewMode) ? 'disabled:cursor-not-allowed disabled:opacity-50' : ''}`}
                value={formData.label || ''}
                onChange={(e) => onChange('label', e.target.value)}
                disabled={isDisabled}
                placeholder="Enter Role name"
                required
            />
    );
}

export default function RolesPage() {
    const { t } = useTranslation();
    const { permissions, flash, auth } = usePage().props as any;
    const [config, setConfig] = useState(rolesConfig);


    //   useEffect(() => {

    //     if (flash?.success) {
    //         toast.success(flash.success);
    //     }else if (flash?.error) {
    //         toast.error(flash.error);
    //     }

    //   }, [flash]);


    // Customize the form fields to handle permissions properly
    useEffect(() => {
        if (permissions) {
            // With tDynamic, we don't need to translate the config here
            setConfig({
                ...rolesConfig,
                table: {
                    ...rolesConfig.table,
                    columns: [
                        ...rolesConfig.table.columns,
                        {
                            key: 'permissions',
                            label: t('Permissions'),
                            render: (value, row) => <PermissionBadges permissions={value || []} />
                        }
                    ]
                },
                form: {
                    ...rolesConfig.form,
                    fields: [
                        ...rolesConfig.form.fields.map(field => {
                            if (field.name === 'label') {
                                return {
                                    ...field,
                                    render: (fieldConfig, formData, onChange, errors, mode) => {
                                        const isDisabled = isDisabledEditRole(formData.name);
                                        return <LabelInput formData={formData} onChange={onChange} isDisabled={isDisabled} isViewMode={mode === 'view'} />;
                                    }
                                };
                            }
                            return field;
                        }).filter(field => field.name !== 'permissions'),
                        {
                            name: 'permissions',
                            label: t('Role Permissions'),
                            type: 'custom',
                            colSpan: 12,
                            render: (field, formData, onChange) => {
                                const PermissionsComponent = () => {
                                    const [disabled, setDisabled] = useState(false);

                                    useEffect(() => {
                                        const checkMode = () => {
                                            const title = document.querySelector('[role="dialog"] h2')?.textContent;
                                            setDisabled(title?.includes('View') || false);
                                        };
                                        checkMode();
                                    }, []);

                                    const accessiblePermissions = disabled
                                        ? Object.keys(permissions).reduce((acc, module) => {
                                            const userPermissionNames = formData.permissions?.map((p: any) =>
                                                typeof p === 'object' ? p.name : p
                                            ) || [];
                                            const filteredPerms = permissions[module].filter((perm: any) =>
                                                userPermissionNames.includes(perm.name)
                                            );
                                            if (filteredPerms.length > 0) {
                                                acc[module] = filteredPerms;
                                            }
                                            return acc;
                                        }, {} as Record<string, any[]>)
                                        : permissions;

                                    return (
                                        <div className="mt-4" id="permissions">
                                            {!disabled &&
                                                <>
                                                    <h3 className="text-lg font-medium mb-2">{t("Manage Permissions")}</h3>
                                                    <p className="text-sm text-gray-500 mb-4">
                                                        {t("Select permissions for this role. You can select all permissions at once or manage them by module.")}
                                                        {auth.user?.type !== 'superadmin' && (
                                                            <span className="block mt-1 text-amber-600">
                                                                {t("Note: Only permissions for modules available to your role are shown.")}
                                                            </span>
                                                        )}
                                                    </p>
                                                </>}
                                            <RolePermissionCheckboxGroup
                                                permissions={accessiblePermissions}
                                                selectedPermissions={formData.permissions || []}
                                                onChange={(selected) => {
                                                    onChange('permissions', selected);
                                                }}
                                                disabled={disabled}
                                            />
                                        </div>
                                    );
                                };
                                return <PermissionsComponent />;
                            }
                        }
                    ]
                },

            });
        }
    }, [permissions, t]);

    const breadcrumbs = [
        { title: t('Dashboard'), href: route('dashboard') },
        { title: t('Staff'), href: route('roles.index') },
        { title: t('Roles') }
    ];

    return (
        <PageCrudWrapper
            config={config}
            url="/roles"
            breadcrumbs={breadcrumbs}
        />
    );
}
