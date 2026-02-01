import { inject } from 'vue';
import type { InertiaFormProps } from '@inertiajs/vue3';

type TypedForm = InertiaFormProps<any> & {
    errors: Record<string, string>;
};

export function useFormField() {
    const form = inject<TypedForm>('form');

    if (!form) {
        throw new Error('useFormField must be used within a Form');
    }

    const getErrors = (field: string) => {
        // More type-safe approach
        return field in form.errors ? form.errors[field as keyof typeof form.errors][0] : null;
    };

    const hasError = (field: string) => {
        return !!getErrors(field);
    };

    return {
        errors: form.errors,
        hasErrors: form.hasErrors,
        hasError,
        getErrors,
        form,
    };
}