export interface Role {
    id: number;
    name: string;
    slug: string;
}

export interface AuthUser {
    id: number;
    name: string;
    email: string;
    role: string | null;
    role_label: string | null;
    status: string;
    initials: string;
}

export interface SchoolInfo {
    name: string;
    short_name: string;
    address: string;
    tagline: string;
}

export interface AcademicYearSummary {
    id: number;
    name: string;
    start_date?: string;
    end_date?: string;
    enrollment_start?: string;
    enrollment_end?: string;
    status?: string;
    is_active?: boolean;
}

export interface FlashProps {
    success?: string | null;
    error?: string | null;
    info?: string | null;
    demo_otp?: string | null;
    demo_activation_url?: string | null;
    import_errors?: { row: number; column: string; message: string }[] | null;
}

export interface SharedPageProps {
    appName: string;
    school: SchoolInfo;
    demoMode: boolean;
    activeYear: AcademicYearSummary | null;
    auth: {
        user: AuthUser | null;
    };
    flash: FlashProps;
    [key: string]: unknown;
}

export interface StudentSummary {
    id: number;
    student_number: string;
    name: string;
    program?: string | null;
    year_level?: string | null;
    status?: string;
}
