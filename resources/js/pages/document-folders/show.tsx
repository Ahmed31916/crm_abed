import { PageTemplate } from '@/components/page-template';
import { usePage } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Folder, FolderOpen, User, Calendar, ArrowLeft } from 'lucide-react';
import { useTranslation } from 'react-i18next';

export default function DocumentFolderShow() {
    const { t } = useTranslation();
    const { documentFolder } = usePage().props as any;

    const breadcrumbs = [
        { title: t('Dashboard'), href: route('dashboard') },
        { title: t('Document Folders'), href: route('document-folders.index') },
        { title: documentFolder.name }
    ];

    return (
        <PageTemplate
            title={documentFolder.name}
            url={`/document-folders/${documentFolder.id}`}
            breadcrumbs={breadcrumbs}
            actions={[
                {
                    label: t('Back'),
                    icon: <ArrowLeft className="h-4 w-4 mr-2" />,
                    variant: 'outline',
                    onClick: () => window.history.back()
                }
            ]}
        >
            <div className="space-y-6">
                {/* Main Information Card */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Folder className="h-5 w-5 text-blue-600" />
                            {t('Folder Information')}
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label className="text-sm font-medium text-gray-500">{t('Folder Name')}</label>
                                <p className="mt-1 text-sm text-gray-900">{documentFolder.name}</p>
                            </div>

                            <div>
                                <label className="text-sm font-medium text-gray-500">{t('Parent Folder')}</label>
                                <p className="mt-1 text-sm text-gray-900">
                                    {documentFolder.parent_folder?.name || t('Root Folder')}
                                </p>
                            </div>

                            <div>
                                <label className="text-sm font-medium text-gray-500">{t('Status')}</label>
                                <div className="mt-1">
                                    <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ${documentFolder.status === 'active' ? 'bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20' : 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/20'}`}>
                                        {documentFolder.status === 'active' ? t('Active') : t('Inactive')}
                                    </span>
                                </div>
                            </div>

                            <div>
                                <label className="text-sm font-medium text-gray-500">{t('Description')}</label>
                                <p className="mt-1 text-sm text-gray-900">
                                    {documentFolder.description || t('No description provided')}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Sub-folders Card */}
                {documentFolder.sub_folders && documentFolder.sub_folders.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <FolderOpen className="h-5 w-5 text-blue-600" />
                                {t('Sub-folders')} ({documentFolder.sub_folders.length})
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                {documentFolder.sub_folders.map((subFolder: any) => (
                                    <div
                                        key={subFolder.id}
                                        className="flex items-center gap-3 p-3 border rounded-lg hover:bg-gray-50 transition-colors"
                                    >
                                        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 text-blue-600">
                                            <Folder className="h-5 w-5" />
                                        </div>
                                        <div className="flex-1 min-w-0">
                                            <p className="text-sm font-medium text-gray-900 truncate">
                                                {subFolder.name}
                                            </p>
                                            <p className="text-xs text-gray-500 truncate">
                                                {subFolder.description || t('No description')}
                                            </p>
                                        </div>
                                        <Badge variant={subFolder.status === 'active' ? 'default' : 'secondary'} className="text-xs">
                                            {subFolder.status === 'active' ? t('Active') : t('Inactive')}
                                        </Badge>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Metadata Card */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Calendar className="h-5 w-5 text-blue-600" />
                            {t('Metadata')}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label className="text-sm font-medium text-gray-500">{t('Created At')}</label>
                                <p className="mt-1 text-sm text-gray-900">
                                    {window.appSettings?.formatDateTime(documentFolder.created_at, false) ||
                                        new Date(documentFolder.created_at).toLocaleString()}
                                </p>
                            </div>

                            <div>
                                <label className="text-sm font-medium text-gray-500">{t('Last Updated')}</label>
                                <p className="mt-1 text-sm text-gray-900">
                                    {window.appSettings?.formatDateTime(documentFolder.updated_at, false) ||
                                        new Date(documentFolder.updated_at).toLocaleString()}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </PageTemplate>
    );
}
