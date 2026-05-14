import { PageTemplate } from '@/components/page-template';
import { usePage, Link } from '@inertiajs/react';
import { ArrowLeft, User, Mail, Calendar, Shield } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useTranslation } from 'react-i18next';

export default function UserShow() {
  const { t } = useTranslation();
  const { user, meetings } = usePage().props as any;

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('Users'), href: route('users.index') },
    { title: user.name }
  ];

  const formatDate = (dateString: string) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    });
  };

  return (
    <PageTemplate
      title={user.name}
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
      <div className="mx-auto space-y-6">
        {/* User Information */}
        <Card className="shadow-sm">
          <CardHeader className="bg-gray-50 border-b px-8 py-6">
            <CardTitle className="flex items-center text-xl font-bold text-gray-800">
              <User className="h-5 w-5 mr-3" />
              {t('User Information')}
            </CardTitle>
          </CardHeader>
          <CardContent className="p-8">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
              <div className="space-y-6">
                <div>
                  <label className="text-xs font-bold text-gray-500 uppercase tracking-wide">{t('Name')}</label>
                  <p className="text-base font-medium text-gray-800 mt-2">{user.name}</p>
                </div>
                <div>
                  <label className="text-xs font-bold text-gray-500 uppercase tracking-wide">{t('Email')}</label>
                  <div className="flex items-center mt-2">
                    <Mail className="h-4 w-4 text-gray-400 mr-2" />
                    <p className="text-base font-medium text-gray-800">{user.email}</p>
                  </div>
                </div>
                <div>
                  <label className="text-xs font-bold text-gray-500 uppercase tracking-wide">{t('Role')}</label>
                  <div className="flex items-center mt-2">
                    <Shield className="h-4 w-4 text-gray-400 mr-2" />
                    <span className="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-600/20">
                      {user.roles?.[0]?.name || user.type || 'No Role'}
                    </span>
                  </div>
                </div>
              </div>
              <div className="space-y-6">
                <div>
                  <label className="text-xs font-bold text-gray-500 uppercase tracking-wide">{t('Status')}</label>
                  <div className="mt-2">
                    <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${
                      user.status === 'active' ? 'bg-green-50 text-green-700 ring-green-600/20' : 'bg-red-50 text-red-700 ring-red-600/20'
                    }`}>
                      {user.status === 'active' ? t('Active') : t('Inactive')}
                    </span>
                  </div>
                </div>
                <div>
                  <label className="text-xs font-bold text-gray-500 uppercase tracking-wide">{t('Created At')}</label>
                  <p className="text-base font-medium text-gray-700 mt-2">{formatDate(user.created_at)}</p>
                </div>
                <div>
                  <label className="text-xs font-bold text-gray-500 uppercase tracking-wide">{t('Created By')}</label>
                  <p className="text-base font-medium text-gray-800 mt-2">{user.creator?.name || 'System'}</p>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Related Meetings */}
        {meetings && meetings.length > 0 && (
          <Card className="shadow-sm">
            <CardHeader className="bg-gray-50 border-b px-8 py-6">
              <CardTitle className="flex items-center text-xl font-bold text-gray-800">
                <Calendar className="h-5 w-5 mr-3" />
                {t('Related Meetings')}
              </CardTitle>
            </CardHeader>
            <CardContent className="p-8">
              <div className="space-y-3">
                {meetings.map((meeting: any) => (
                  <div key={meeting.id} className="p-4 bg-purple-50 rounded-lg border border-purple-200 hover:shadow-md transition-shadow">
                    <div className="flex items-center justify-between">
                      <div>
                        <Link
                          href={route('meetings.show', meeting.id)}
                          className="text-base font-medium text-purple-700 hover:text-purple-900 hover:underline transition-colors"
                        >
                          {meeting.title}
                        </Link>
                        <p className="text-sm text-gray-600 mt-1">
                          {new Date(meeting.start_date).toLocaleDateString()} - {meeting.assigned_user?.name || 'Unassigned'}
                        </p>
                      </div>
                      <span className={`px-2 py-1 text-xs font-medium rounded-full ${
                        meeting.status === 'completed' ? 'bg-green-100 text-green-800' :
                        meeting.status === 'in_progress' ? 'bg-blue-100 text-blue-800' :
                        'bg-gray-100 text-gray-800'
                      }`}>
                        {meeting.status?.replace('_', ' ').replace(/\b\w/g, (l: string) => l.toUpperCase())}
                      </span>
                    </div>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>
        )}
      </div>
    </PageTemplate>
  );
}
