import type { SVGAttributes } from 'react';

export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg {...props} viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg">
            <rect width="40" height="40" rx="10" fill="#047857" />
            <g
                fill="none"
                stroke="white"
                strokeWidth="2"
                strokeLinecap="round"
                strokeLinejoin="round"
            >
                <path d="m8 15 12-7 12 7H8Z" />
                <path d="M11 19v9m6-9v9m6-9v9m6-9v9M8 32h24" />
            </g>
        </svg>
    );
}
