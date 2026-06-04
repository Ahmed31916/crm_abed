import React from 'react';
import PlanForm from './form';

interface Feature {
    feature_name: string;
    feature_value: string;
}

interface Plan {
    id: number;
    name: string;
    price: number;
    yearly_price: number | null;
    is_plan_enable: string;
    is_default: boolean;
    features: Feature[];
}

interface Props {
    plan: Plan;
    otherDefaultPlanExists: boolean;
}

export default function EditPlan({ plan, otherDefaultPlanExists }: Props) {
    return <PlanForm plan={plan} otherDefaultPlanExists={otherDefaultPlanExists} />;
}
