import React, { useEffect } from 'react';
import { useEditor, EditorContent } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import TextAlign from '@tiptap/extension-text-align';
import { TextStyle } from '@tiptap/extension-text-style';
import { Color } from '@tiptap/extension-color';
import Highlight from '@tiptap/extension-highlight';
import Image from '@tiptap/extension-image';
import { Table } from '@tiptap/extension-table';
import { TableRow } from '@tiptap/extension-table-row';
import { TableCell } from '@tiptap/extension-table-cell';
import { TableHeader } from '@tiptap/extension-table-header';
import Placeholder from '@tiptap/extension-placeholder';
import {
    Bold, Italic, Strikethrough, List, ListOrdered, Quote, Code, Undo2, Redo2,
    Heading1, Heading2, Link2, ImageIcon, Table2, AlignLeft, AlignCenter, AlignRight,
    Highlighter, Palette,
} from 'lucide-react';

type Props = {
    value: string;
    onChange: (html: string) => void;
    placeholder?: string;
    className?: string;
};

export default function RichTextEditor({ value, onChange, placeholder = 'Start typing…', className = '' }: Props) {
    const editor = useEditor({
        immediatelyRender: false,
        extensions: [
            StarterKit.configure({
                heading: { levels: [1, 2, 3] },
                link: {
                    openOnClick: false,
                    autolink: true,
                    HTMLAttributes: { class: 'text-indigo-600 underline' },
                },
            }),
            TextStyle,
            Color,
            Highlight.configure({ multicolor: true }),
            TextAlign.configure({ types: ['heading', 'paragraph'] }),
            Image.configure({ HTMLAttributes: { class: 'rounded-lg max-w-full h-auto my-2' } }),
            Table.configure({
                resizable: true,
                HTMLAttributes: { class: 'border-collapse border border-gray-300 dark:border-gray-600 w-full my-2' },
            }),
            TableRow,
            TableHeader,
            TableCell,
            Placeholder.configure({ placeholder }),
        ],
        content: value || '',
        editorProps: {
            attributes: {
                class:
                    'prose prose-sm dark:prose-invert max-w-none min-h-[200px] px-3 py-2 focus:outline-none ' +
                    'border border-gray-200 dark:border-gray-600 rounded-b-lg bg-white dark:bg-gray-800 ' +
                    'text-gray-900 dark:text-gray-100 [&_img]:max-w-full',
            },
        },
        onUpdate: ({ editor }) => {
            onChange(editor.getHTML());
        },
    });

    useEffect(() => {
        if (!editor) return;
        const cur = editor.getHTML();
        const next = value || '';
        if (next !== cur) {
            editor.commands.setContent(next, { emitUpdate: false });
        }
    }, [value, editor]);

    if (!editor) {
        return <div className="animate-pulse h-[240px] rounded-lg bg-gray-100 dark:bg-gray-800" />;
    }

    const btn = (active: boolean) =>
        `p-1.5 rounded ${active ? 'bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'}`;

    const addLink = () => {
        const prev = editor.getAttributes('link').href as string | undefined;
        const url = window.prompt('Link URL', prev || 'https://');
        if (url === null) return;
        if (url === '') {
            editor.chain().focus().extendMarkRange('link').unsetLink().run();
            return;
        }
        editor.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
    };

    const addImage = () => {
        const url = window.prompt('Image URL', 'https://');
        if (!url) return;
        editor.chain().focus().setImage({ src: url }).run();
    };

    return (
        <div className={`rounded-lg overflow-hidden ${className}`}>
            <div className="flex flex-wrap gap-0.5 p-2 border border-b-0 border-gray-200 dark:border-gray-600 rounded-t-lg bg-gray-50 dark:bg-gray-900">
                <button type="button" onClick={() => editor.chain().focus().toggleBold().run()} className={btn(editor.isActive('bold'))} title="Bold">
                    <Bold size={16} />
                </button>
                <button type="button" onClick={() => editor.chain().focus().toggleItalic().run()} className={btn(editor.isActive('italic'))} title="Italic">
                    <Italic size={16} />
                </button>
                <button type="button" onClick={() => editor.chain().focus().toggleUnderline().run()} className={btn(editor.isActive('underline'))} title="Underline">
                    <span className="text-sm font-semibold underline px-0.5">U</span>
                </button>
                <button type="button" onClick={() => editor.chain().focus().toggleStrike().run()} className={btn(editor.isActive('strike'))} title="Strikethrough">
                    <Strikethrough size={16} />
                </button>
                <span className="w-px h-5 bg-gray-300 dark:bg-gray-600 mx-1 self-center" />
                <button type="button" onClick={() => editor.chain().focus().toggleHeading({ level: 1 }).run()} className={btn(editor.isActive('heading', { level: 1 }))} title="Heading 1">
                    <Heading1 size={16} />
                </button>
                <button type="button" onClick={() => editor.chain().focus().toggleHeading({ level: 2 }).run()} className={btn(editor.isActive('heading', { level: 2 }))} title="Heading 2">
                    <Heading2 size={16} />
                </button>
                <span className="w-px h-5 bg-gray-300 dark:bg-gray-600 mx-1 self-center" />
                <button type="button" onClick={() => editor.chain().focus().toggleBulletList().run()} className={btn(editor.isActive('bulletList'))} title="Bullet list">
                    <List size={16} />
                </button>
                <button type="button" onClick={() => editor.chain().focus().toggleOrderedList().run()} className={btn(editor.isActive('orderedList'))} title="Ordered list">
                    <ListOrdered size={16} />
                </button>
                <button type="button" onClick={() => editor.chain().focus().toggleBlockquote().run()} className={btn(editor.isActive('blockquote'))} title="Quote">
                    <Quote size={16} />
                </button>
                <button type="button" onClick={() => editor.chain().focus().toggleCodeBlock().run()} className={btn(editor.isActive('codeBlock'))} title="Code block">
                    <Code size={16} />
                </button>
                <span className="w-px h-5 bg-gray-300 dark:bg-gray-600 mx-1 self-center" />
                <button type="button" onClick={() => editor.chain().focus().setTextAlign('left').run()} className={btn(editor.isActive({ textAlign: 'left' }))} title="Align left">
                    <AlignLeft size={16} />
                </button>
                <button type="button" onClick={() => editor.chain().focus().setTextAlign('center').run()} className={btn(editor.isActive({ textAlign: 'center' }))} title="Align center">
                    <AlignCenter size={16} />
                </button>
                <button type="button" onClick={() => editor.chain().focus().setTextAlign('right').run()} className={btn(editor.isActive({ textAlign: 'right' }))} title="Align right">
                    <AlignRight size={16} />
                </button>
                <span className="w-px h-5 bg-gray-300 dark:bg-gray-600 mx-1 self-center" />
                <label className={`${btn(false)} cursor-pointer flex items-center gap-1`} title="Text color">
                    <Palette size={16} />
                    <input
                        type="color"
                        className="w-5 h-5 p-0 border-0 rounded cursor-pointer"
                        onInput={(e) => editor.chain().focus().setColor((e.target as HTMLInputElement).value).run()}
                    />
                </label>
                <button type="button" onClick={() => editor.chain().focus().toggleHighlight({ color: '#fef08a' }).run()} className={btn(editor.isActive('highlight'))} title="Highlight">
                    <Highlighter size={16} />
                </button>
                <span className="w-px h-5 bg-gray-300 dark:bg-gray-600 mx-1 self-center" />
                <button type="button" onClick={addLink} className={btn(editor.isActive('link'))} title="Link">
                    <Link2 size={16} />
                </button>
                <button type="button" onClick={addImage} className={btn(false)} title="Image from URL">
                    <ImageIcon size={16} />
                </button>
                <button
                    type="button"
                    onClick={() => editor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run()}
                    className={btn(false)}
                    title="Insert table"
                >
                    <Table2 size={16} />
                </button>
                <span className="w-px h-5 bg-gray-300 dark:bg-gray-600 mx-1 self-center" />
                <button type="button" onClick={() => editor.chain().focus().undo().run()} className={btn(false)} title="Undo">
                    <Undo2 size={16} />
                </button>
                <button type="button" onClick={() => editor.chain().focus().redo().run()} className={btn(false)} title="Redo">
                    <Redo2 size={16} />
                </button>
            </div>
            <EditorContent editor={editor} />
        </div>
    );
}
