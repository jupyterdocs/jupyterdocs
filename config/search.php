<?php

return [

    // Words too common to help find anything. Deliberately short: "notes",
    // "study" and "guide" are things people genuinely search for. A word
    // that appears as a synonym below (e.g. "it") is never dropped.
    'stopwords' => [
        'a', 'an', 'and', 'are', 'as', 'at', 'be', 'by', 'do', 'for', 'from',
        'has', 'have', 'how', 'in', 'is', 'it', 'its', 'of', 'on', 'or', 'that',
        'the', 'this', 'to', 'was', 'were', 'what', 'when', 'where', 'which',
        'who', 'will', 'with', 'about', 'can', 'i', 'me', 'my', 'we', 'you',
    ],

    // Groups of terms that mean the same thing. Searching any member also
    // finds documents using the others (at a slightly lower rank than an
    // exact match). Members may be several words. Extend freely.
    'synonyms' => [
        ['it', 'information technology'],
        ['os', 'operating system'],
        ['db', 'database'],
        ['dbms', 'database management system'],
        ['ai', 'artificial intelligence'],
        ['ml', 'machine learning'],
        ['dl', 'deep learning'],
        ['nlp', 'natural language processing'],
        ['oop', 'object oriented programming'],
        ['dsa', 'data structures algorithms'],
        ['cs', 'computer science'],
        ['se', 'software engineering'],
        ['hci', 'human computer interaction'],
        ['ui', 'user interface'],
        ['ux', 'user experience'],
        ['iot', 'internet of things'],
        ['math', 'maths', 'mathematics'],
        ['calc', 'calculus'],
        ['stats', 'statistics'],
        ['econ', 'economics'],
        ['chem', 'chemistry'],
        ['bio', 'biology'],
        ['phys', 'physics'],
        ['accounting', 'accounts'],
        ['exam', 'exams', 'examination', 'examinations'],
        ['prog', 'programming'],
        ['lecture', 'lectures', 'lecture notes'],
        ['slides', 'powerpoint', 'pptx', 'ppt'],
        ['spreadsheet', 'excel', 'xlsx', 'xls'],
        ['cv', 'resume', 'curriculum vitae'],
    ],

    // How much a match in each place is worth. Title is king, like a
    // search engine's page title; description is the weakest signal.
    'weights' => [
        'title' => 10,
        'tag' => 6,
        'format' => 4,
        'course' => 5,
        'university' => 3,
        'type' => 3,
        'description' => 2.5,
    ],

    // Guardrails so a one-letter query can't pull the whole table.
    'max_index_rows' => 30000,
    'refine_top' => 300,
    'per_page' => 12,
];
