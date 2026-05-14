import { useState, useEffect } from 'react'
import { Head, Link, router, usePage } from '@inertiajs/react'
import { PageTemplate } from '@/components/page-template'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Badge } from '@/components/ui/badge'
import { RichTextField } from '@/components/ui/rich-text-field'
import { ArrowLeft, Save } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { toast } from '@/components/custom-toast'

interface EmailTemplateLang {
    id: number
    lang: string
    subject: string
    content: string
}

interface EmailTemplate {
    id: number
    name: string
    from: string
    email_template_langs: EmailTemplateLang[]
}

interface Language {
    code: string
    name: string
    countryCode: string
}

interface Props {
    template: EmailTemplate
    languages: Language[]
    variables: Record<string, string>
}

export default function EmailTemplateShow({ template, languages, variables }: Props) {
    const { t } = useTranslation()
    const { flash } = usePage().props as any
    const [fromName, setFromName] = useState(template.from)
    const [currentLang, setCurrentLang] = useState(languages[0]?.code || 'en')
    const [templateLangs, setTemplateLangs] = useState(
        template.email_template_langs.reduce((acc, lang) => {
            acc[lang.lang] = {
                subject: lang.subject,
                content: lang.content
            }
            return acc
        }, {} as Record<string, { subject: string; content: string }>)
    )

    const handleSubjectChange = (lang: string, subject: string) => {
        setTemplateLangs(prev => ({
            ...prev,
            [lang]: { ...prev[lang], subject }
        }))
    }

    const handleContentChange = (lang: string, content: string) => {
        setTemplateLangs(prev => ({
            ...prev,
            [lang]: { ...prev[lang], content }
        }))
    }

    const handleSave = () => {
        alert('Save functionality will be implemented later')
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
        { title: t('Email Templates'), href: route('email-templates.index') },
        { title: template.name }
    ]

    return (
        <PageTemplate
            title={template.name}
            url={route('email-templates.show', template.id)}
            breadcrumbs={breadcrumbs}
            actions={[
                {
                    label: t('Back'),
                    icon: <ArrowLeft className="h-4 w-4 mr-2" />,
                    variant: 'outline',
                    onClick: () => router.visit(route('email-templates.index'))
                }
            ]}
        >
            <Head title={`Edit Template - ${template.name}`} />

            <div className="grid gap-6 lg:grid-cols-3">
                <div className="lg:col-span-2 space-y-6">
                    <Card>
                        <CardHeader>
                            <div className="flex justify-between items-center">
                                <CardTitle>{t("Template Settings")}</CardTitle>
                                <Button
                                    onClick={() => {
                                        router.put(route('email-templates.update-settings', template.id), {
                                            from: fromName
                                        })
                                    }}
                                    size="sm"
                                >
                                    <Save className="h-4 w-4 mr-2" />
                                    {t("Save Changes")}
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid gap-2">
                                <Label>{t("Template Name")}</Label>
                                <Input value={template.name} disabled className="bg-muted" />
                                <p className="text-xs text-muted-foreground">{t("Template name cannot be changed")}</p>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="from" required>{t("From Name")}</Label>
                                <Input
                                    id="from"
                                    value={fromName}
                                    onChange={(e) => setFromName(e.target.value)}
                                    placeholder={t("Enter from name (e.g., {app_name}, Support Team)")}
                                    className="focus:ring-2 focus:ring-primary"
                                />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <div className="flex justify-between items-center">
                                <div>
                                    <CardTitle>{t("Email Content")}</CardTitle>
                                    <p className="text-sm text-muted-foreground mt-1">
                                        {t("Customize email content for different languages")}
                                    </p>
                                </div>
                                <Button
                                    onClick={() => {
                                        const currentContent = templateLangs[currentLang]
                                        if (currentContent) {
                                            router.put(route('email-templates.update-content', template.id), {
                                                lang: currentLang,
                                                subject: currentContent.subject,
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
                            <Tabs defaultValue={languages[0]?.code} onValueChange={setCurrentLang} className="w-full">
                                <div className="mb-4">
                                    <div className="overflow-x-auto">
                                        <TabsList className="inline-flex h-auto p-1 w-max">
                                            {languages.map((language) => (
                                                <TabsTrigger
                                                    key={language.code}
                                                    value={language.code}
                                                    className="text-xs px-3 py-2 whitespace-nowrap data-[state=active]:bg-primary data-[state=active]:text-primary-foreground"
                                                >
                                                    {language.code.toUpperCase()}
                                                </TabsTrigger>
                                            ))}
                                        </TabsList>
                                    </div>
                                </div>

                                {languages.map((language) => (
                                    <TabsContent key={language.code} value={language.code} className="space-y-6 mt-6">
                                        <div className="flex items-center gap-3 p-3 bg-muted/50 rounded-lg">
                                            <Badge variant="default" className="px-3 py-1">
                                                {language.code.toUpperCase()}
                                            </Badge>
                                            <div>
                                                <span className="font-medium">{language.name}</span>
                                                <p className="text-xs text-muted-foreground">{t("Edit email content for this language")}</p>
                                            </div>
                                        </div>

                                        <div className="space-y-4">
                                            <div className="grid gap-3">
                                                <Label htmlFor={`subject-${language.code}`} className="text-sm font-medium" required>
                                                    {t("Email Subject")}
                                                </Label>
                                                <Input
                                                    id={`subject-${language.code}`}
                                                    value={templateLangs[language.code]?.subject || ''}
                                                    onChange={(e) => handleSubjectChange(language.code, e.target.value)}
                                                    placeholder={t("Enter email subject (you can use variables like {app_name})")}
                                                    className="focus:ring-2 focus:ring-primary"
                                                />
                                            </div>

                                            <div className="space-y-3">
                                                <RichTextField
                                                    label="Email Content"
                                                    value={templateLangs[language.code]?.content || ''}
                                                    onChange={(content) => handleContentChange(language.code, content)}
                                                    placeholder={t("Write your email content here. You can use HTML formatting and variables...")}
                                                    className="min-h-[300px]"
                                                />
                                                <p className="text-xs text-muted-foreground">
                                                    💡 {t("Tip: Use the variables from the sidebar to personalize your emails")}
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
                                {t("Click to copy variables to use in your email content")}
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
                                    💡 <strong>{t("Tip")}:</strong> {t("These variables will be automatically replaced with actual values when emails are sent.")}
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </PageTemplate>
    )
}
