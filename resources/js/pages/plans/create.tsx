import React from 'react';
import PlanForm from './form';

interface Feature {
    feature_name: string;
    feature_value: string;
}

interface Props {
    hasDefaultPlan: boolean;
}

export default function CreatePlan({ hasDefaultPlan }: Props) {
    return <PlanForm hasDefaultPlan={hasDefaultPlan} />;
}
