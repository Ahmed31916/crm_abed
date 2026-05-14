import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { Edit, ArrowLeft, CheckCircle, Clock, User, Calendar, BarChart3, FileText, Briefcase } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { hasPermission } from '@/utils/authorization';
import { useTranslation } from 'react-i18next';

export default function ProjectTaskShow() {
  const { t } = useTranslation();
  const { auth, task, taskStatuses = [] } = usePage().props as any;
  const permissions = auth?.permissions || [];

  const handleEdit = () => {
    router.get(route('project-tasks.index'), {}, {
      onSuccess: () => {
        // This will trigger the edit modal in the index page
        // You might need to pass the task ID as a query parameter
      }
    });
  };

  const handleBack = () => {
    router.get(route('project-tasks.index'));
  };

  const pageActions = [];

  pageActions.push({
    label: t('Back'),
    icon: <ArrowLeft className="h-4 w-4 mr-2" />,
    variant: 'outline',
    onClick: handleBack
  });

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('Project Tasks'), href: route('project-tasks.index') },
    { title: task.title }
  ];

  const getTaskStatus = (taskStatusId: number) => {
    return taskStatuses.find((ts: any) => ts.id === taskStatusId);
  };

  const getPriorityColor = (priority: string) => {
    const colors = {
      low: 'bg-gray-100 text-gray-800',
      medium: 'bg-blue-100 text-blue-800',
      high: 'bg-orange-100 text-orange-800',
      urgent: 'bg-red-100 text-red-800'
    };
    return colors[priority as keyof typeof colors] || colors.medium;
  };

  return (
    <PageTemplate
      title={task.title}
      url={`/project-tasks/${task.id}`}
      actions={pageActions}
      breadcrumbs={breadcrumbs}
    >
      <div className="space-y-6">
        {/* Task Overview */}
        <Card>
          <CardHeader>
            <div className="flex items-center justify-between">
              <CardTitle className="flex items-center gap-2">
                <FileText className="h-5 w-5" />
                {t('Task Overview')}
              </CardTitle>
              <div className="flex gap-2">
                {(() => {
                  const taskStatus = getTaskStatus(task.task_status_id);
                  return taskStatus ? (
                    <div className="flex items-center gap-2">
                      <div
                        className="w-3 h-3 rounded-full"
                        style={{ backgroundColor: taskStatus.color }}
                      ></div>
                      <span className="text-sm font-medium">{taskStatus.name}</span>
                    </div>
                  ) : (
                    <span className="text-sm text-gray-500">{t('No Status')}</span>
                  );
                })()}
                <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ${
                  task.priority === 'urgent' ? 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/20' :
                  task.priority === 'high' ? 'bg-orange-50 text-orange-700 ring-1 ring-inset ring-orange-600/20' :
                  task.priority === 'medium' ? 'bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-600/20' :
                  'bg-gray-50 text-gray-700 ring-1 ring-inset ring-gray-600/20'
                }`}>
                  {t((task.priority || '').charAt(0).toUpperCase() + (task.priority || '').slice(1))} {t('Priority')}
                </span>
              </div>
            </div>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div className="space-y-4">
                <div>
                  <label className="text-sm font-medium text-gray-500 dark:text-gray-400">{t('Description')}</label>
                  <p className="mt-1 text-gray-900 dark:text-white">
                    {task.description || t('No description provided')}
                  </p>
                </div>

                <div>
                  <label className="text-sm font-medium text-gray-500 dark:text-gray-400">{t('Project')}</label>
                  <div className="mt-1 flex items-center gap-2">
                    <Briefcase className="h-4 w-4 text-gray-400" />
                    <span className="text-gray-900 dark:text-white">
                      {task.project?.name || t('No project assigned')}
                    </span>
                  </div>
                </div>

                {task.parent && (
                  <div>
                    <label className="text-sm font-medium text-gray-500 dark:text-gray-400">{t('Parent Task')}</label>
                    <div className="mt-1 flex items-center gap-2">
                      <FileText className="h-4 w-4 text-gray-400" />
                      <span className="text-gray-900 dark:text-white">{task.parent.title}</span>
                    </div>
                  </div>
                )}

                <div>
                  <label className="text-sm font-medium text-gray-500 dark:text-gray-400">{t('Assigned To')}</label>
                  <div className="mt-1 flex items-center gap-2">
                    <User className="h-4 w-4 text-gray-400" />
                    <span className="text-gray-900 dark:text-white">
                      {task.assigned_user?.name || t('Unassigned')}
                    </span>
                  </div>
                </div>
              </div>

              <div className="space-y-4">
                <div>
                  <label className="text-sm font-medium text-gray-500 dark:text-gray-400">{t('Progress')}</label>
                  <div className="mt-1">
                    <div className="flex items-center justify-between mb-1">
                      <span className="text-sm text-gray-600 dark:text-gray-300">{task.progress}%</span>
                    </div>
                    <div className="w-full bg-gray-200 rounded-full h-2">
                      <div
                        className="bg-blue-600 h-2 rounded-full transition-all duration-300"
                        style={{ width: `${task.progress}%` }}
                      ></div>
                    </div>
                  </div>
                </div>

                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <label className="text-sm font-medium text-gray-500 dark:text-gray-400">{t('Start Date')}</label>
                    <div className="mt-1 flex items-center gap-2">
                      <Calendar className="h-4 w-4 text-gray-400" />
                      <span className="text-gray-900 dark:text-white">
                        {task.start_date ? (window.appSettings?.formatDateTime(task.start_date, false) || new Date(task.start_date).toLocaleDateString()) : t('Not set')}
                      </span>
                    </div>
                  </div>

                  <div>
                    <label className="text-sm font-medium text-gray-500 dark:text-gray-400">{t('Due Date')}</label>
                    <div className="mt-1 flex items-center gap-2">
                      <Calendar className="h-4 w-4 text-gray-400" />
                      <span className="text-gray-900 dark:text-white">
                        {task.due_date ? (window.appSettings?.formatDateTime(task.due_date, false) || new Date(task.due_date).toLocaleDateString()) : t('Not set')}
                      </span>
                    </div>
                  </div>
                </div>

                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <label className="text-sm font-medium text-gray-500 dark:text-gray-400">{t('Estimated Hours')}</label>
                    <div className="mt-1 flex items-center gap-2">
                      <Clock className="h-4 w-4 text-gray-400" />
                      <span className="text-gray-900 dark:text-white">
                        {task.estimated_hours ? `${task.estimated_hours}h` : t('Not set')}
                      </span>
                    </div>
                  </div>

                  <div>
                    <label className="text-sm font-medium text-gray-500 dark:text-gray-400">{t('Actual Hours')}</label>
                    <div className="mt-1 flex items-center gap-2">
                      <BarChart3 className="h-4 w-4 text-gray-400" />
                      <span className="text-gray-900 dark:text-white">
                        {task.actual_hours ? `${task.actual_hours}h` : t('Not tracked')}
                      </span>
                    </div>
                  </div>
                </div>

                <div>
                  <label className="text-sm font-medium text-gray-500 dark:text-gray-400">{t('Created By')}</label>
                  <div className="mt-1 flex items-center gap-2">
                    <User className="h-4 w-4 text-gray-400" />
                    <span className="text-gray-900 dark:text-white">
                      {task.creator?.name || t('Unknown')}
                    </span>
                  </div>
                </div>

                <div>
                  <label className="text-sm font-medium text-gray-500 dark:text-gray-400">{t('Created At')}</label>
                  <div className="mt-1 flex items-center gap-2">
                    <Calendar className="h-4 w-4 text-gray-400" />
                    <span className="text-gray-900 dark:text-white">
                      {window.appSettings?.formatDateTime(task.created_at, false) || new Date(task.created_at).toLocaleDateString()}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Subtasks */}
        {task.subtasks && task.subtasks.length > 0 && (
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <FileText className="h-5 w-5" />
                {t('Subtasks')} ({task.subtasks.length})
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-3">
                {task.subtasks.map((subtask: any) => (
                  <div key={subtask.id} className="flex items-center justify-between p-3 border border-gray-200 dark:border-gray-700 rounded-lg">
                    <div className="flex items-center gap-3">
                      <div
                        className="h-3 w-3 rounded-full"
                        style={{ backgroundColor: getTaskStatus(subtask.task_status_id)?.color || '#6B7280' }}
                      ></div>
                      <div>
                        <h4 className="font-medium text-gray-900 dark:text-white">{subtask.title}</h4>
                        <p className="text-sm text-gray-500 dark:text-gray-400">
                          {subtask.assigned_user?.name || t('Unassigned')} • {subtask.progress}% {t('complete')}
                        </p>
                      </div>
                    </div>
                    <div className="flex items-center gap-2">
                      {(() => {
                        const subtaskStatus = getTaskStatus(subtask.task_status_id);
                        return subtaskStatus ? (
                          <span className="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium bg-gray-50 text-gray-700 ring-1 ring-inset ring-gray-600/20">
                            {subtaskStatus.name}
                          </span>
                        ) : (
                          <span className="text-xs text-gray-500">{t('No Status')}</span>
                        );
                      })()}
                      <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ${
                        subtask.priority === 'urgent' ? 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/20' :
                        subtask.priority === 'high' ? 'bg-orange-50 text-orange-700 ring-1 ring-inset ring-orange-600/20' :
                        subtask.priority === 'medium' ? 'bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-600/20' :
                        'bg-gray-50 text-gray-700 ring-1 ring-inset ring-gray-600/20'
                      }`}>
                        {t((subtask.priority || '').charAt(0).toUpperCase() + (subtask.priority || '').slice(1))}
                      </span>
                    </div>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>
        )}

        {/* Time Tracking Summary */}
        {(task.estimated_hours || task.actual_hours) && (
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <BarChart3 className="h-5 w-5" />
                {t('Time Tracking')}
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div className="text-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                  <div className="text-2xl font-bold text-blue-600 dark:text-blue-400">
                    {task.estimated_hours || 0}h
                  </div>
                  <div className="text-sm text-gray-600 dark:text-gray-400">{t('Estimated')}</div>
                </div>
                <div className="text-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                  <div className="text-2xl font-bold text-green-600 dark:text-green-400">
                    {task.actual_hours || 0}h
                  </div>
                  <div className="text-sm text-gray-600 dark:text-gray-400">{t('Actual')}</div>
                </div>
                <div className="text-center p-4 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
                  <div className="text-2xl font-bold text-orange-600 dark:text-orange-400">
                    {task.estimated_hours && task.actual_hours
                      ? Math.round(((task.actual_hours - task.estimated_hours) / task.estimated_hours) * 100)
                      : 0}%
                  </div>
                  <div className="text-sm text-gray-600 dark:text-gray-400">{t('Variance')}</div>
                </div>
              </div>
            </CardContent>
          </Card>
        )}
      </div>
    </PageTemplate>
  );
}
