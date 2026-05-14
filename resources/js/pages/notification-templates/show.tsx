import { useState, useEffect } from 'react'
import { Head, router, usePage } from '@inertiajs/react'
import { PageTemplate } from '@/components/page-template'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Badge } from '@/components/ui/badge'
import { ArrowLeft, Save } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { toast } from '@/components/custom-toast'

interface NotificationTemplate {
    id: number
    name: string
    notification_template_langs: Array<{
        id: number
        lang: string
        title: string
        content: string
    }>
}

interface Props {
    template: NotificationTemplate
    languages: Record<string, string>
    variables: Record<string, string>
}

export default function NotificationTemplateShow({ template, languages, variables }: Props) {
    const { t } = useTranslation()
    const { flash } = usePage().props as any
    const [currentLang, setCurrentLang] = useState(Object.keys(languages)[0] || 'en')
    const [templateLangs, setTemplateLangs] = useState(
        template.notification_template_langs.reduce((acc, lang) => {
            acc[lang.lang] = {
                title: lang.title,
                content: lang.content
            }
            return acc
        }, {} as Record<string, { title: string; content: string }>)
    )

    const handleTitleChange = (lang: string, title: string) => {
        setTemplateLangs(prev => ({
            ...prev,
            [lang]: { ...prev[lang], title }
        }))
    }

    const handleContentChange = (lang: string, content: string) => {
        setTemplateLangs(prev => ({
            ...prev,
            [lang]: { ...prev[lang], content }
        }))
    }

    // Handle flash messages
    useEffect(() => {
        if (flash?.success) {
            toast.success(flash.success)
        }
        if (flash?.error) {
            toast.error(flash.error)
        }
    }, [flash])

    const breadcrumbs = [
        { title: t('Dashboard'), href: route('dashboard') },
        { title: t('Notification Templates'), href: route('notification-templates.index') },
        { title: template.name }
    ]

    return (
        <PageTemplate
            title={template.name}
            url={route('notification-templates.show', template.id)}
            breadcrumbs={breadcrumbs}
            actions={[
                {
                    label: t('Back'),
                    icon: <ArrowLeft className="h-4 w-4 mr-2" />,
                    variant: 'outline',
                    onClick: () => router.visit(route('notification-templates.index'))
                }
            ]}
        >
            <Head title={`${template.name} - Notification Templates`} />

            <div className="grid gap-6 lg:grid-cols-3">
                <div className="lg:col-span-2 space-y-6">
                    <Card>
                        <CardHeader>
                            <div className="flex justify-between items-center">
                                <CardTitle>{t("Template Settings")}</CardTitle>
                            </div>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid gap-2">
                                <Label>{t("Template Name")}</Label>
                                <Input
                                    value={template.name}
                                    placeholder={t("Enter template name")}
                                    disabled
                                />
                            </div>
                        </CardContent>
                    </Card>


                    <Card>
                        <CardHeader>
                            <div className="flex justify-between items-center">
                                <div>
                                    <CardTitle>{t("Notification Content")}</CardTitle>
                                    <p className="text-sm text-muted-foreground mt-1">
                                        {t("Customize notification content for different languages")}
                                    </p>
                                </div>
                                <Button
                                    onClick={() => {
                                        const currentContent = templateLangs[currentLang]
                                        if (currentContent) {
                                            router.put(route('notification-templates.update-content', template.id), {
                                                lang: currentLang,
                                                title: currentContent.title,
                                                content: currentContent.content
                                            })
                                        }
                                    }}
                                    size="sm"
                                    className="shrink-0"
                                >
                                    <Save className="h-4 w-4 mr-2" />
                                    {t("Save Content")}
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <Tabs defaultValue={Object.keys(languages)[0]} onValueChange={setCurrentLang} className="w-full">
                                <div className="mb-4">
                                    <div className="overflow-x-auto">
                                        <TabsList className="inline-flex h-auto p-1 w-max">
                                            {Object.entries(languages).map(([code, name]) => (
                                                <TabsTrigger
                                                    key={code}
                                                    value={code}
                                                    className="text-xs px-3 py-2 whitespace-nowrap data-[state=active]:bg-primary data-[state=active]:text-primary-foreground"
                                                >
                                                    {code.toUpperCase()}
                                                </TabsTrigger>
                                            ))}
                                        </TabsList>
                                    </div>
                                </div>

                                {Object.entries(languages).map(([code, name]) => (
                                    <TabsContent key={code} value={code} className="space-y-6 mt-6">
                                        <div className="flex items-center gap-3 p-3 bg-muted/50 rounded-lg">
                                            <Badge variant="default" className="px-3 py-1">
                                                {code.toUpperCase()}
                                            </Badge>
                                            <div>
                                                <span className="font-medium">{name}</span>
                                                <p className="text-xs text-muted-foreground">{t("Edit notification content for this language")}</p>
                                            </div>
                                        </div>

                                        <div className="space-y-4">
                                            <div className="grid gap-3">
                                                <Label htmlFor={`title-${code}`} className="text-sm font-medium" required>
                                                    {t("Notification Title")}
                                                </Label>
                                                <Input
                                                    id={`title-${code}`}
                                                    value={templateLangs[code]?.title || ''}
                                                    onChange={(e) => handleTitleChange(code, e.target.value)}
                                                    placeholder={t("Enter notification title (you can use variables like {company_name})")}
                                                    className="focus:ring-2 focus:ring-primary"
                                                />
                                            </div>

                                            <div className="space-y-3">
                                                <Label htmlFor={`content-${code}`} className="text-sm font-medium" required>
                                                    {t("Notification Content")}
                                                </Label>
                                                <Textarea
                                                    id={`content-${code}`}
                                                    value={templateLangs[code]?.content || ''}
                                                    onChange={(e) => handleContentChange(code, e.target.value)}
                                                    placeholder={t("Write your notification content here. You can use variables...")}
                                                    className="min-h-[200px] focus:ring-2 focus:ring-primary"
                                                    rows={8}
                                                />
                                                <p className="text-xs text-muted-foreground">
                                                    💡 {t("Tip: Use the variables from the sidebar to personalize your notifications")}
                                                </p>
                                            </div>
                                        </div>
                                    </TabsContent>
                                ))}
                            </Tabs>
                        </CardContent>
                    </Card>
                </div>

                <div>
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <span>{t("Available Variables")}</span>
                                <Badge variant="secondary" className="text-xs">
                                    {Object.keys(variables).length}
                                </Badge>
                            </CardTitle>
                            <p className="text-sm text-muted-foreground mt-1">
                                {t("Click to copy variables to use in your notification content")}
                            </p>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-3">
                                {Object.entries(variables).map(([variable, description]) => (
                                    <div
                                        key={variable}
                                        className="group p-3 bg-muted/50 rounded-lg border hover:bg-muted/80 cursor-pointer transition-colors"
                                        onClick={() => {
                                            navigator.clipboard.writeText(variable)
                                            toast.success(t('Variable copied to clipboard'))
                                        }}
                                    >
                                        <div className="flex items-center justify-between">
                                            <code className="text-sm font-mono text-primary font-medium bg-background px-1.5 py-0.5 rounded">
                                                {variable}
                                            </code>
                                            <div className="opacity-0 group-hover:opacity-100 transition-opacity">
                                                <Badge variant="outline" className="text-xs">
                                                    {t("Click to copy")}
                                                </Badge>
                                            </div>
                                        </div>
                                        <p className="text-xs text-muted-foreground mt-2 leading-relaxed">
                                            {description}
                                        </p>
                                    </div>
                                ))}
                            </div>
                            <div className="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                                <p className="text-xs text-blue-700">
                                    💡 <strong>{t("Tip")}:</strong> {t("These variables will be automatically replaced with actual values when notifications are sent.")}
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </PageTemplate>
    )
}
