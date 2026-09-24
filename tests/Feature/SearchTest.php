<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Resource;
use App\Models\ResourceType;
use App\Models\Tag;
use App\Models\User;
use App\Support\Search\Highlighter;
use App\Support\Search\ResourceSearch;
use App\Support\Search\ResourceSearchIndexer;
use App\Support\Search\Tokenizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    private function type(string $name = 'Notes'): ResourceType
    {
        return ResourceType::firstOrCreate(['slug' => str($name)->slug()->toString()], ['name' => $name]);
    }

    /**
     * @param  list<string>  $tags
     */
    private function doc(string $title, string $description = '', array $tags = [], array $attributes = []): Resource
    {
        $resource = Resource::create([
            'resource_type_id' => $this->type()->id,
            'title' => $title,
            'description' => $description ?: null,
            'file_path' => 'resources/x.pdf',
            'file_size' => 10,
            'format' => 'pdf',
            'status' => 'approved',
            ...$attributes,
        ]);

        if ($tags) {
            $resource->tags()->sync(collect($tags)->map(
                fn ($name) => Tag::firstOrCreate(['slug' => str($name)->slug()->toString()], ['name' => $name])->id
            ));

            app(ResourceSearchIndexer::class)->index($resource);
        }

        return $resource;
    }

    /**
     * @return list<int>
     */
    private function ids(string $query, ?int $typeId = null, bool $correct = true): array
    {
        return app(ResourceSearch::class)->search($query, $typeId, $correct)->ids;
    }

    // -- Text analysis --------------------------------------------------------

    public function test_words_are_folded_lowercased_and_keep_programming_symbols(): void
    {
        $this->assertSame(['c++', 'cafes', 'oreilly', 'c#'], Tokenizer::words("C++ & Café's O'Reilly C#"));
    }

    public function test_word_forms_share_a_stem_but_unrelated_words_do_not(): void
    {
        $this->assertSame('program', Tokenizer::prefixRoot('programming'));
        $this->assertSame('program', Tokenizer::prefixRoot('programs'));
        $this->assertSame('program', Tokenizer::prefixRoot('program'));
        $this->assertSame('mathemat', Tokenizer::prefixRoot('mathematics'));
        $this->assertSame('profession', Tokenizer::prefixRoot('professionals'));
        $this->assertSame('operat', Tokenizer::prefixRoot('operating'));
        $this->assertNull(Tokenizer::prefixRoot('data'));

        // A bare plural must not become a stem that swallows other words.
        $this->assertSame('physics', Tokenizer::prefixRoot('physics'));
        $this->assertFalse(str_starts_with('physician', Tokenizer::prefixRoot('physics')));

        $this->assertContains('study', Tokenizer::variants('studies'));
        $this->assertContains('systems', Tokenizer::variants('system'));
        $this->assertContains('class', Tokenizer::variants('classes'));
    }

    public function test_edit_distance_counts_a_swapped_pair_as_one_typo(): void
    {
        $this->assertSame(1, Tokenizer::distance('mathematcs', 'mathematics'));
        $this->assertSame(1, Tokenizer::distance('form', 'from'));
        $this->assertSame(3, Tokenizer::distance('kitten', 'sitting', 5));
        $this->assertGreaterThan(2, Tokenizer::distance('algebra', 'biology', 2));
    }

    // -- Ranking --------------------------------------------------------------

    public function test_title_matches_outrank_mentions_in_the_description(): void
    {
        $mention = $this->doc('Physics Basics', 'Uses linear algebra a lot.');
        $titled = $this->doc('Linear Algebra Notes');

        $this->assertSame([$titled->id, $mention->id], $this->ids('linear algebra'));
    }

    public function test_search_ignores_filler_words_and_case_like_a_web_search(): void
    {
        $target = $this->doc('Mathematics for IT Professionals');
        $this->doc('Mathematics of Music');
        $this->doc('Professionals in Nursing');

        $ids = $this->ids('MATHEMATICS for IT professionals');

        // The document covering every word wins, and documents matching just
        // one of the words are not padded in behind it.
        $this->assertSame([$target->id], $ids);
    }

    public function test_a_document_covering_more_of_the_query_beats_one_covering_less(): void
    {
        $partial = $this->doc('Chemistry Lab Safety');
        $full = $this->doc('Organic Chemistry Lab Manual');

        $this->assertSame($full->id, $this->ids('organic chemistry lab')[0]);
        $this->assertNotContains($partial->id, array_slice($this->ids('organic chemistry lab'), 0, 1));
    }

    public function test_popular_documents_win_when_relevance_is_equal(): void
    {
        $quiet = $this->doc('Statistics Cheatsheet');
        $popular = $this->doc('Statistics Cheatsheet ', attributes: ['downloads_count' => 400]);

        $this->assertSame($popular->id, $this->ids('statistics cheatsheet')[0]);
    }

    public function test_search_only_returns_approved_documents_and_respects_the_type_filter(): void
    {
        $approved = $this->doc('Algorithms Handbook');
        $this->doc('Algorithms Secrets', attributes: ['status' => 'pending']);
        $paper = $this->doc('Algorithms Past Paper', attributes: ['resource_type_id' => $this->type('Past Paper')->id]);

        $this->assertEqualsCanonicalizing([$approved->id, $paper->id], $this->ids('algorithms'));
        $this->assertSame([$paper->id], $this->ids('algorithms', $paper->resource_type_id));
    }

    // -- Understanding words --------------------------------------------------

    public function test_other_forms_of_a_word_still_match(): void
    {
        $doc = $this->doc('Programming Fundamentals');

        $this->assertSame([$doc->id], $this->ids('programs'));
        $this->assertSame([$doc->id], $this->ids('program'));
        $this->assertSame([$this->doc('Classes and Objects')->id], $this->ids('class'));
    }

    public function test_abbreviations_and_synonyms_find_the_long_form(): void
    {
        $os = $this->doc('Operating Systems Lecture Notes');
        $maths = $this->doc('Maths for Engineers');

        $this->assertSame([$os->id], $this->ids('os'));
        $this->assertSame([$maths->id], $this->ids('mathematics'));
    }

    public function test_tags_courses_and_file_format_are_searchable(): void
    {
        $tagged = $this->doc('Week 4 Handout', tags: ['thermodynamics']);
        $course = Course::create(['name' => 'Organic Chemistry', 'code' => 'CHM201', 'slug' => 'organic-chemistry']);
        $inCourse = $this->doc('Reaction Mechanisms', attributes: ['course_id' => $course->id]);
        $slides = $this->doc('Lecture 1', attributes: ['format' => 'pptx']);

        $this->assertSame([$tagged->id], $this->ids('thermodynamics'));
        $this->assertSame([$inCourse->id], $this->ids('organic chemistry'));
        $this->assertSame([$inCourse->id], $this->ids('chm201'));
        $this->assertContains($slides->id, $this->ids('powerpoint'));
    }

    // -- Operators ------------------------------------------------------------

    public function test_quoted_phrases_must_appear_together(): void
    {
        $together = $this->doc('Data Structures Guide');
        $this->doc('Structures of Data Science');

        $this->assertSame([$together->id], $this->ids('"data structures"'));
    }

    public function test_a_minus_sign_excludes_documents(): void
    {
        $this->doc('Java Programming');
        $python = $this->doc('Python Programming');

        $this->assertSame([$python->id], $this->ids('programming -java'));
    }

    // -- Spelling ---------------------------------------------------------------

    public function test_misspelt_words_are_corrected_against_words_in_the_library(): void
    {
        $doc = $this->doc('Mathematics Handbook');

        $result = app(ResourceSearch::class)->search('mathematcs');

        $this->assertSame([$doc->id], $result->ids);
        $this->assertSame(['mathematcs' => 'mathematics'], $result->corrections);
        $this->assertSame('mathematics', $result->correctedQuery);
    }

    public function test_a_real_word_that_matches_nothing_in_this_type_is_not_miscorrected(): void
    {
        $this->doc('Physics Handbook');
        $chemistry = $this->doc('Chemistry Handbook', attributes: ['resource_type_id' => $this->type('Past Paper')->id]);

        // "physics" exists in the library, just not in Past Papers — that
        // is no reason to rewrite it into something else.
        $result = app(ResourceSearch::class)->search('physics', $chemistry->resource_type_id);

        $this->assertSame([], $result->ids);
        $this->assertSame([], $result->corrections);
    }

    public function test_typo_correction_can_be_switched_off(): void
    {
        $this->doc('Mathematics Handbook');

        $this->assertSame([], $this->ids('mathematcs', correct: false));
    }

    // -- The browse page ----------------------------------------------------------

    public function test_browse_page_shows_the_corrected_query_and_a_way_back(): void
    {
        $this->doc('Mathematics Handbook');

        $this->get(route('resources.index', ['q' => 'mathematcs']))
            ->assertOk()
            ->assertSee('Showing results for')
            ->assertSee('Search instead for')
            ->assertSee('1 result');

        $this->get(route('resources.index', ['q' => 'mathematcs', 'exact' => 1]))
            ->assertOk()
            ->assertDontSee('Showing results for')
            ->assertSee('No documents match');
    }

    public function test_results_highlight_matches_and_never_let_titles_inject_html(): void
    {
        $this->doc('Calculus <script>alert(1)</script> Notes', 'Limits and derivatives with worked calculus examples.');

        $response = $this->get(route('resources.index', ['q' => 'calculus']))->assertOk();

        $response->assertSee('<mark class="jd-mark">Calculus</mark>', false);
        $response->assertDontSee('<script>alert(1)</script>', false);
        $response->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_highlighter_marks_word_forms_and_cuts_a_snippet_around_the_match(): void
    {
        $highlighter = new Highlighter([], ['program']);

        $this->assertSame(
            'Learn <mark class="jd-mark">programming</mark> &amp; more',
            (string) $highlighter->highlight('Learn programming & more')
        );

        $long = str_repeat('filler words ', 30).'programming appears here'.str_repeat(' trailing words', 30);
        $snippet = (string) $highlighter->snippet($long, 100);

        $this->assertStringContainsString('<mark class="jd-mark">programming</mark>', $snippet);
        $this->assertStringStartsWith('…', $snippet);
        $this->assertLessThan(200, mb_strlen(strip_tags($snippet)));
    }

    public function test_a_query_with_no_usable_words_just_browses(): void
    {
        $this->doc('Anything At All');

        $this->get(route('resources.index', ['q' => '"']))->assertOk()->assertSee('Anything At All');
        $this->get(route('resources.index', ['q' => '-']))->assertOk()->assertSee('Anything At All');
    }

    public function test_ranked_pagination_keeps_the_query_and_the_order(): void
    {
        foreach (range(1, 14) as $n) {
            $this->doc("Thermodynamics Volume {$n}");
        }

        $this->get(route('resources.index', ['q' => 'thermodynamics']))
            ->assertOk()
            ->assertSee('14 results')
            ->assertSee('q=thermodynamics', false);

        $this->get(route('resources.index', ['q' => 'thermodynamics', 'page' => 2]))
            ->assertOk()
            ->assertViewHas('resources', fn ($resources) => $resources->count() === 2);
    }

    // -- Suggestions ------------------------------------------------------------------

    public function test_suggestions_offer_topics_titles_and_courses_as_you_type(): void
    {
        $this->doc('Thermodynamics Past Paper', tags: ['thermodynamics']);
        $this->doc('Thermal Secrets', attributes: ['status' => 'pending']);

        $response = $this->getJson(route('search.suggest', ['q' => 'thermo']))->assertOk();

        $suggestions = collect($response->json('suggestions'));

        $this->assertTrue($suggestions->contains(fn ($s) => $s['text'] === 'thermodynamics' && $s['type'] === 'Topic'));
        $this->assertTrue($suggestions->contains(fn ($s) => $s['text'] === 'Thermodynamics Past Paper' && $s['type'] === 'Document'));
        $this->assertFalse($suggestions->contains(fn ($s) => str_contains($s['text'], 'Secrets')));
    }

    public function test_suggestions_need_at_least_two_characters_and_ignore_wildcards(): void
    {
        $this->doc('Algebra Notes');

        $this->getJson(route('search.suggest', ['q' => 'a']))->assertOk()->assertJson(['suggestions' => []]);
        $this->getJson(route('search.suggest', ['q' => '%%']))->assertOk()->assertJson(['suggestions' => []]);
    }

    // -- Keeping the index in step --------------------------------------------------------

    public function test_uploading_indexes_the_document_including_its_generated_tags(): void
    {
        Storage::fake(config('filesystems.resource_disk'));

        $this->actingAs(User::factory()->create())->post(route('resources.store'), [
            'title' => 'Mathematics for IT Professionals',
            'description' => 'A short introduction to discrete structures',
            'resource_type_id' => $this->type()->id,
            'confirm_ownership' => '1',
            'file' => UploadedFile::fake()->create('notes.pdf', 500, 'application/pdf'),
        ])->assertRedirect();

        $resource = Resource::firstOrFail();

        $this->assertTrue(DB::table('resource_search_terms')
            ->where('resource_id', $resource->id)
            ->where('field', ResourceSearchIndexer::TAG)
            ->exists());

        // Pending until approved, then findable.
        $this->assertSame([], $this->ids('professionals'));

        $resource->update(['status' => 'approved']);

        $this->assertSame([$resource->id], $this->ids('professionals'));
    }

    public function test_editing_a_document_updates_what_it_is_found_by(): void
    {
        $doc = $this->doc('Old Title Here');

        $doc->update(['title' => 'Quantum Mechanics Primer']);

        $this->assertSame([], $this->ids('old'));
        $this->assertSame([$doc->id], $this->ids('quantum'));
    }

    public function test_reindex_command_rebuilds_a_wiped_index(): void
    {
        $doc = $this->doc('Genetics Overview');
        DB::table('resource_search_terms')->delete();

        $this->assertSame([], $this->ids('genetics'));

        $this->artisan('search:reindex')->assertSuccessful();

        $this->assertSame([$doc->id], $this->ids('genetics'));
    }
}
