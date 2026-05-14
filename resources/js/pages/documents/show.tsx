import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { Edit, Trash2, Download, FileText, Calendar, User, Building, Folder, Tag, Target, ArrowLeft, Eye } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { hasPermission } from '@/utils/authorization';
import { CrudFormModal } from '@/components/CrudFormModal';
import { CrudDeleteModal } from '@/components/CrudDeleteModal';
import { toast } from '@/components/custom-toast';
import { useTranslation } from 'react-i18next';
import { useState } from 'react';

export default function DocumentShow() {
  const { t } = useTranslation();
  const {
    auth,
    document,
    users = [],
    accounts = [],
    folders = [],
    types = [],
    opportunities = []
  } = usePage().props as any;
  const permissions = auth?.permissions || [];

  const [isFormModalOpen, setIsFormModalOpen] = useState(false);
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [formMode, setFormMode] = useState<'edit' | 'view'>('view');

  const handleEdit = () => {
    setFormMode('edit');
    setIsFormModalOpen(true);
  };

  const handleDelete = () => {
    setIsDeleteModalOpen(true);
  };

  const handleDownload = () => {
    if (document.attachment_url) {
      const link = window.document.createElement('a');
      link.href = route('documents.download', document.id);
      link.download = '';
      window.document.body.appendChild(link);
      link.click();
      window.document.body.removeChild(link);
    } else {
      toast.error(t('No attachment available for download'));
    }
  };

  const handleFormSubmit = (formData: any) => {
    toast.loading(t('Updating document...'));

    router.put(route("documents.update", document.id), formData, {
      onSuccess: (page) => {
        setIsFormModalOpen(false);
        toast.dismiss();
        if (page.props.flash.success) {
          toast.success(t(page.props.flash.success));
        } else if (page.props.flash.error) {
          toast.error(t(page.props.flash.error));
        }
      },
      onError: (errors) => {
        toast.dismiss();
        if (typeof errors === 'string') {
          toast.error(errors);
        } else {
          toast.error(t('Failed to update: {{errors}}', { errors: Object.values(errors).join(', ') }));
        }
      }
    });
  };

  const handleDeleteConfirm = () => {
    toast.loading(t('Deleting document...'));

    router.delete(route('documents.destroy', document.id), {
      onSuccess: (page) => {
        toast.dismiss();
        if (page.props.flash.success) {
          toast.success(t(page.props.flash.success));
        } else if (page.props.flash.error) {
          toast.error(t(page.props.flash.error));
        }
        // Redirect to documents index after successful deletion
        router.get(route('documents.index'));
      },
      onError: (errors) => {
        toast.dismiss();
        if (typeof errors === 'string') {
          toast.error(errors);
        } else {
          toast.error(t('Failed to delete: {{errors}}', { errors: Object.values(errors).join(', ') }));
        }
      }
    });
  };

  const handleToggleStatus = () => {
    const newStatus = document.status === 'active' ? 'inactive' : 'active';
    toast.loading(`${newStatus === 'active' ? t('Activating') : t('Deactivating')} document...`);

    router.put(route('documents.toggle-status', document.id), {}, {
      onSuccess: (page) => {
        toast.dismiss();
        if (page.props.flash.success) {
          toast.success(t(page.props.flash.success));
        } else if (page.props.flash.error) {
          toast.error(t(page.props.flash.error));
        }
      },
      onError: (errors) => {
        toast.dismiss();
        if (typeof errors === 'string') {
          toast.error(errors);
        } else {
          toast.error(t('Failed to update status: {{errors}}', { errors: Object.values(errors).join(', ') }));
        }
      }
    });
  };

  // Define page actions
  const pageActions = [
    {
      label: t('Back'),
      icon: <ArrowLeft className="h-4 w-4 mr-2" />,
      variant: 'outline',
      onClick: () => router.get(route('documents.index'))
    }
  ];

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('Documents'), href: route('documents.index') },
    { title: document.name }
  ];

  return (
    <PageTemplate
      title={document.name}
      url={`/documents/${document.id}`}
      actions={pageActions}
      breadcrumbs={breadcrumbs}
    >
      <div className="mx-auto space-y-6">
        {/* Header Section */}
        <div className="bg-white rounded-lg shadow-sm border p-6">
          <div className="flex items-center justify-between">
            <div>
              <h1 className="text-lg font-bold">{document.name}</h1>
              <p className="text-sm mt-2">{document.type?.type_name || t('No type specified')}</p>
            </div>
            <div className="text-right">
              <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${
                document.status === 'active'
                  ? 'bg-green-50 text-green-700 ring-green-600/20'
                  : 'bg-red-50 text-red-700 ring-red-600/10'
              }`}>
                {document.status === 'active' ? t('Active') : t('Inactive')}
              </span>
            </div>
          </div>
        </div>

        {/* Document Information */}
        <Card className="shadow-sm">
          <CardHeader className="bg-gray-50 border-b">
            <CardTitle className="text-lg font-semibold">{t('Document Information')}</CardTitle>
          </CardHeader>
          <CardContent className="p-6">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div className="space-y-6">
                <div className="flex items-center space-x-3">
                  <Building className="h-5 w-5 text-muted-foreground" />
                  <div>
                    <label className="text-sm font-medium text-muted-foreground">{t('Account')}</label>
                    <p className="text-sm mt-1">{document.account?.name || t('-')}</p>
                  </div>
                </div>
                <div className="flex items-center space-x-3">
                  <Folder className="h-5 w-5 text-muted-foreground" />
                  <div>
                    <label className="text-sm font-medium text-muted-foreground">{t('Folder')}</label>
                    <p className="text-sm mt-1">{document.folder?.name || t('-')}</p>
                  </div>
                </div>
                <div className="flex items-center space-x-3">
                  <Tag className="h-5 w-5 text-muted-foreground" />
                  <div>
                    <label className="text-sm font-medium text-muted-foreground">{t('Type')}</label>
                    <p className="text-sm mt-1">{document.type?.type_name || t('-')}</p>
                  </div>
                </div>
              </div>
              <div className="space-y-6">
                <div className="flex items-center space-x-3">
                  <Target className="h-5 w-5 text-muted-foreground" />
                  <div>
                    <label className="text-sm font-medium text-muted-foreground">{t('Opportunity')}</label>
                    <p className="text-sm mt-1">{document.opportunity?.name || t('-')}</p>
                  </div>
                </div>
                <div className="flex items-center space-x-3">
                  <User className="h-5 w-5 text-muted-foreground" />
                  <div>
                    <label className="text-sm font-medium text-muted-foreground">{t('Assigned To')}</label>
                    <p className="text-sm mt-1">{document.assigned_user?.name || t('Unassigned')}</p>
                  </div>
                </div>
                <div>
                  <label className="text-sm font-medium text-muted-foreground">{t('Status')}</label>
                  <div className="mt-1">
                    <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${
                      document.status === 'active'
                        ? 'bg-green-50 text-green-700 ring-green-600/20'
                        : 'bg-red-50 text-red-700 ring-red-600/10'
                    }`}>
                      {document.status === 'active' ? t('Active') : t('Inactive')}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Description */}
        {document.description && (
          <Card className="shadow-sm">
            <CardHeader className="bg-gray-50 border-b">
              <CardTitle className="flex items-center text-lg font-semibold">
                <FileText className="h-5 w-5 mr-3 text-muted-foreground" />
                {t('Description')}
              </CardTitle>
            </CardHeader>
            <CardContent className="p-6">
              <p className="text-sm whitespace-pre-wrap">{document.description}</p>
            </CardContent>
          </Card>
        )}

        {/* Attachment Details */}
        {document.attachment_url && (
          <Card className="shadow-sm">
            <CardHeader className="bg-gray-50 border-b">
              <CardTitle className="flex items-center text-lg font-semibold">
                <Download className="h-5 w-5 mr-3 text-muted-foreground" />
                {t('Attachment')}
              </CardTitle>
            </CardHeader>
            <CardContent className="p-6">
              <div className="flex items-center justify-between p-4 bg-blue-50 rounded-lg border border-blue-200">
                <div className="flex items-center space-x-3">
                  <FileText className="h-8 w-8 text-blue-500" />
                  <div>
                    <p className="font-medium text-gray-900">
                      {document.attachment_name || t('Attachment')}
                    </p>
                    {document.attachment_size && (
                      <p className="text-sm text-gray-500">
                        {(document.attachment_size / 1024 / 1024).toFixed(2)} MB
                      </p>
                    )}
                  </div>
                </div>
                <div className="flex gap-2">
                  <Button onClick={() => window.open(document.attachment_url, '_blank')} variant="outline" size="sm" className="bg-white">
                    <Eye className="h-4 w-4 mr-2" />
                    {t('View')}
                  </Button>
                  <Button onClick={handleDownload} variant="outline" size="sm" className="bg-white">
                    <Download className="h-4 w-4 mr-2" />
                    {t('Download')}
                  </Button>
                </div>
              </div>
            </CardContent>
          </Card>
        )}

        {/* Important Dates */}
        <Card className="shadow-sm">
          <CardHeader className="bg-gray-50 border-b">
            <CardTitle className="flex items-center text-lg font-semibold">
              <Calendar className="h-5 w-5 mr-3 text-muted-foreground" />
              {t('Important Dates')}
            </CardTitle>
          </CardHeader>
          <CardContent className="p-6">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              {document.publish_date && (
                <div>
                  <label className="text-sm font-medium text-muted-foreground">{t('Publish Date')}</label>
                  <p className="text-sm mt-1">
                    {window.appSettings?.formatDateTime(document.publish_date, false) || new Date(document.publish_date).toLocaleDateString()}
                  </p>
                </div>
              )}
              {document.expiration_date && (
                <div>
                  <label className="text-sm font-medium text-muted-foreground">{t('Expiration Date')}</label>
                  <p className="text-sm mt-1">
                    {window.appSettings?.formatDateTime(document.expiration_date, false) || new Date(document.expiration_date).toLocaleDateString()}
                  </p>
                </div>
              )}
              <div>
                <label className="text-sm font-medium text-muted-foreground">{t('Created At')}</label>
                <p className="text-sm mt-1">
                  {window.appSettings?.formatDateTime(document.created_at, false) || new Date(document.created_at).toLocaleDateString()}
                </p>
              </div>
              <div>
                <label className="text-sm font-medium text-muted-foreground">{t('Updated At')}</label>
                <p className="text-sm mt-1">
                  {window.appSettings?.formatDateTime(document.updated_at, false) || new Date(document.updated_at).toLocaleDateString()}
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Form Modal */}
      <CrudFormModal
        isOpen={isFormModalOpen}
        onClose={() => setIsFormModalOpen(false)}
        onSubmit={handleFormSubmit}
        formConfig={{
          fields: [
            { name: 'name', label: t('Document Name'), type: 'text', required: true },
            {
              name: 'account_id',
              label: t('Account'),
              type: 'select',
              options: [
                { value: 'null', label: t('No Account') },
                ...accounts.map((account: any) => ({
                  value: account.id,
                  label: account.name
                }))
              ]
            },
            {
              name: 'folder_id',
              label: t('Folder'),
              type: 'select',
              options: [
                { value: 'null', label: t('No Folder') },
                ...folders.map((folder: any) => ({
                  value: folder.id,
                  label: folder.name
                }))
              ]
            },
            {
              name: 'type_id',
              label: t('Type'),
              type: 'select',
              options: [
                { value: 'null', label: t('No Type') },
                ...types.map((type: any) => ({
                  value: type.id,
                  label: type.type_name
                }))
              ]
            },
            {
              name: 'opportunity_id',
              label: t('Opportunity'),
              type: 'select',
              options: [
                { value: 'null', label: t('No Opportunity') },
                ...opportunities.map((opportunity: any) => ({
                  value: opportunity.id,
                  label: opportunity.name
                }))
              ]
            },
            { name: 'publish_date', label: t('Publish Date'), type: 'date' },
            { name: 'expiration_date', label: t('Expiration Date'), type: 'date' },
            {
              name: 'attachment',
              label: t('Attachment'),
              type: 'media-picker',
              placeholder: t('Select file...')
            },
            {
              name: 'assigned_to',
              label: t('Assign To'),
              type: 'select',
              options: [
                { value: 'null', label: t('Unassigned') },
                ...users.map((user: any) => ({ value: user.id, label: `${user.name} (${user.email})` }))
              ]
            },
            { name: 'description', label: t('Description'), type: 'textarea' },
            {
              name: 'status',
              label: t('Status'),
              type: 'select',
              options: [
                { value: 'active', label: t('Active') },
                { value: 'inactive', label: t('Inactive') }
              ]
            }
          ],
          modalSize: 'xl'
        }}
        initialData={document}
        title={t('Edit Document')}
        mode={formMode}
      />

      {/* Delete Modal */}
      <CrudDeleteModal
        isOpen={isDeleteModalOpen}
        onClose={() => setIsDeleteModalOpen(false)}
        onConfirm={handleDeleteConfirm}
        itemName={document.name || ''}
        entityName={t('document')}
      />
    </PageTemplate>
  );
}
