import React, { useEffect, useRef, useState, forwardRef, useImperativeHandle } from 'react';
import { createPortal } from 'react-dom';

export interface IframePortalHandles {
    print: () => void;
}

interface IframePortalProps {
    children: React.ReactNode;
}

const IframePortal = forwardRef<IframePortalHandles, IframePortalProps>(
    ({ children }, ref) => {
        const iframeRef = useRef<HTMLIFrameElement>(null);
        const [mountNode, setMountNode] = useState<HTMLElement | null>(null);

        // Expose print method to parent
        useImperativeHandle(ref, () => ({
            print: () => {
                if (!iframeRef.current) return;
                const win = iframeRef.current.contentWindow!;
                win.focus();
                win.print();
            },
        }));

        useEffect(() => {
            if (!iframeRef.current) return;

            const doc = iframeRef.current.contentDocument!;
            doc.open();
            doc.write(`
                <!DOCTYPE html>
                <html>
                    <head>
                        <style>
                            html, body {
                                background: #ffffff;
                                color: #000000;
                                font-family: Lato, sans-serif;
                                color-scheme: light;
                                overflow:hidden;
                            }
                            @media print {
                                body {
                                    -webkit-print-color-adjust: exact;
                                    print-color-adjust: exact;
                                }
                            }
                        </style>
                    </head>
                    <body>
                        <div id="iframe-root"></div>
                    </body>
                </html>
            `);
            doc.close();

            const root = doc.getElementById('iframe-root')!;
            setMountNode(root);

            // Auto resize iframe
            const resize = () => {
                iframeRef.current!.style.height = doc.body.scrollHeight + 'px';
            };
            resize();
            const observer = new ResizeObserver(resize);
            observer.observe(doc.body);

            return () => observer.disconnect();
        }, []);

        return (
            <>
                <iframe
                    ref={iframeRef}
                    style={{ width: '100%', border: 'none', background: '#fff' }}
                />
                {mountNode && createPortal(children, mountNode)}
            </>
        );
    }
);

export default IframePortal;
