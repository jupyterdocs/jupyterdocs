# {{ config('app.name', 'JupyterDocs') }}

> A free, student-built archive of lecture notes, past papers, study guides, textbooks and handbooks. Students upload documents, an admin reviews each one, and anyone can search the archive and read previews online. Downloads are unlocked by contributing approved uploads.

## Key pages

- [Browse and search the archive]({{ route('resources.index') }}): full-text search across every approved document, filterable by type of resource.
- [Upload a document]({{ route('resources.create') }}): open to guests as well as account holders.
- [Sitemap]({{ route('sitemap') }}): every public document page.

## How it works

- Anyone can search the archive and read approved documents page by page in the browser.
- Any student can upload PDF, Word, PowerPoint, Excel or plain text files; Word, PowerPoint and Excel files are converted to PDF for previewing.
- An admin reviews every upload before it is published, usually within a day.
- Every account starts with a free download; two approved uploads unlock downloads across the archive.

## Policies

- [Terms of Service]({{ route('terms') }})
- [Privacy Policy]({{ route('privacy') }})
- [Copyright Policy]({{ route('copyright') }}): how rights holders can request removal of material.
- [Contact]({{ route('contact') }})
