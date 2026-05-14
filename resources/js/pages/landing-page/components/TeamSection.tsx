import React, { useState } from 'react';
import { Linkedin, Twitter, Mail } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { getDisplayUrl } from '@/utils/helper';
import { Link } from '@inertiajs/react';

interface TeamSectionProps {
    brandColor?: string;
    settings?: any;
    sectionData?: {
        title?: string;
        subtitle?: string;
        members?: Array<{
            name: string;
            role: string;
            bio: string;
            image?: string;
            linkedin?: string;
            twitter?: string;
            email?: string;
        }>;
        cta_title?: string;
        cta_description?: string;
        cta_button_text?: string;
    };
}

export default function TeamSection({ settings, sectionData, brandColor = '#3b82f6' }: TeamSectionProps) {
    const [imgErrors, setImgErrors] = useState<Set<number>>(new Set());

    const handleImgError = (index: number) => {
        setImgErrors(prev => new Set(prev).add(index));
    };
    const { t } = useTranslation();
    const defaultMembers = [
        { name: 'Sarah Johnson', role: t('CEO & Founder'), bio: t('Sales strategist and former tech executive with 15+ years of experience in scaling SaaS businesses.'), image: '', linkedin: '#', email: 'sarah@sales.com' },
        { name: 'Michael Lee', role: t('CTO'), bio: t('Full-stack engineer specializing in Laravel and React with a passion for building scalable SaaS platforms.'), image: '', linkedin: '#', email: 'michael@sales.com' },
        { name: 'Priya Sharma', role: t('Head of Product'), bio: t('Product leader focused on delivering user-friendly sales solutions that solve real-world challenges.'), image: '', linkedin: '#', email: 'priya@sales.com' },
        { name: 'David Kim', role: t('Head of Marketing'), bio: t('Growth marketer with expertise in SaaS positioning, customer acquisition, and brand strategy.'), image: '', linkedin: '#', email: 'david@sales.com' }
    ];

    const teamMembers = sectionData?.members && sectionData.members.length > 0
        ? sectionData.members
        : defaultMembers;

    return (
        <section className="py-12 sm:py-16 lg:py-20 bg-white">
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div className="text-center mb-8 sm:mb-12 lg:mb-16">
                    <h2 className="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                        {sectionData?.title || t('Meet Our Team')}
                    </h2>
                    <p className="text-lg text-gray-600 max-w-3xl mx-auto leading-relaxed font-medium">
                        {sectionData?.subtitle || t('We are a passionate group of sales experts, developers, and innovators building the future of sales automation.')}
                    </p>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 sm:gap-8">
                    {teamMembers.map((member, index) => (
                        <div key={index} className="bg-gray-50 rounded-xl p-6 border border-gray-200 hover:border-gray-300 transition-colors">
                            {/* Profile Image */}
                            <div className="w-20 h-20 rounded-full mx-auto mb-4 flex items-center justify-center overflow-hidden" style={{ backgroundColor: brandColor }}>
                                {member?.image && !imgErrors.has(index) ? (
                                    <img
                                        src={getDisplayUrl(member.image)}
                                        alt={member?.name || 'Team Member'}
                                        className="w-full h-full object-cover"
                                        onError={() => handleImgError(index)}
                                    />
                                ) : (
                                    <span className="text-white text-lg font-bold">
                                        {member.name.split(' ').map(n => n[0]).join('')}
                                    </span>
                                )}
                            </div>

                            {/* Member Info */}
                            <div className="text-center">
                                <h3 className="text-lg font-semibold text-gray-900 mb-1">
                                    {member.name}
                                </h3>
                                <p className="text-gray-700 font-medium mb-3">
                                    {member.role}
                                </p>
                                <p className="text-gray-600 text-sm leading-relaxed mb-4">
                                    {member.bio}
                                </p>

                                {/* Social Links */}
                                <div className="flex justify-center gap-2">
                                    {member.linkedin && (
                                        <a
                                            href={member.linkedin}
                                            className="w-8 h-8 bg-white rounded-full flex items-center justify-center border border-gray-200 hover:border-gray-300 transition-colors"
                                        >
                                            <Linkedin className="w-4 h-4 text-gray-600" />
                                        </a>
                                    )}
                                    {member.twitter && (
                                        <a
                                            href={member.twitter}
                                            className="w-8 h-8 bg-white rounded-full flex items-center justify-center border border-gray-200 hover:border-gray-300 transition-colors"
                                        >
                                            <Twitter className="w-4 h-4 text-gray-600" />
                                        </a>
                                    )}
                                    {member.email && (
                                        <a
                                            href={`mailto:${member.email}`}
                                            className="w-8 h-8 bg-white rounded-full flex items-center justify-center border border-gray-200 hover:border-gray-300 transition-colors"
                                        >
                                            <Mail className="w-4 h-4 text-gray-600" />
                                        </a>
                                    )}
                                </div>
                            </div>
                        </div>
                    ))}
                </div>

                {/* Join Team CTA */}
                {(sectionData?.cta_title || sectionData?.cta_description || sectionData?.cta_button_text) && (
                    <div className="text-center mt-8 sm:mt-12 lg:mt-16">
                        <div className="bg-gray-50 rounded-xl p-8 border border-gray-200 max-w-2xl mx-auto">
                            <h3 className="text-2xl font-bold text-gray-900 mb-4">
                                {sectionData?.cta_title || t('Want to Join Our Team?')}
                            </h3>
                            <p className="text-gray-600 mb-6">
                                {sectionData?.cta_description || t('We are always looking for talented individuals to help us shape the next generation of sales technology.')}
                            </p>
                            <Link
                                href={'#contact'}
                                className="inline-flex items-center px-8 py-3 rounded-lg transition-all font-semibold border"
                                style={{ backgroundColor: brandColor, color: 'white', borderColor: brandColor }}
                                onMouseEnter={(e) => {
                                    e.currentTarget.style.backgroundColor = 'white';
                                    e.currentTarget.style.color = brandColor;
                                }}
                                onMouseLeave={(e) => {
                                    e.currentTarget.style.backgroundColor = brandColor;
                                    e.currentTarget.style.color = 'white';
                                }}
                            >
                                {sectionData?.cta_button_text || 'View Open Positions'}
                            </Link>
                        </div>
                    </div>
                )}
            </div>
        </section>
    );
}
