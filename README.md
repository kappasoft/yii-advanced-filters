# Yii Advanced Filters

The Yii Advanced Filters extension improves Yii's grid view filters by allowing users to enter more powerful search terms. Multiple filter expressions can be combined together when filtering a single column, allowing for complex filters to be applied.

By default, the extension will understand the following patterns when they are entered into a grid filter:

<table class="table table-condensed">
    <thead>
        <tr>
            <th class="col-md-3 col-xs-4">
                Syntax
            </th>
            <th class="col-md-9 col-xs-8">
                Description
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>
                word1 word2 word3
            </td>
            <td>All words must appear in any order.</td>
        </tr>
        <tr>
            <td>
                <strong class="text-danger">"</strong>search term<strong
                    class="text-danger">"</strong>
            </td>
            <td>The value must match the search term exactly.</td>
        </tr>
        <tr>
            <td>
                <strong class="text-danger">#</strong>search term<strong class="text-danger">#</strong>
            </td>
            <td>The value must contain the exact search term.</td>
        </tr>
        <tr>
            <td>
                <strong class="text-danger">/</strong>regex<strong
                    class="text-danger">/</strong>
            </td>

            <td>The value must match the regular expression pattern.</td>
        </tr>
        <tr>
            <td>
                n1 <strong class="text-primary">to</strong> n2
            </td>
            <td>Numerically between n1 and n2 inclusive.</td>
        </tr>
        <tr>
            <td>
                <strong class="text-primary">&lt;</strong>
                n1&nbsp;&nbsp;&nbsp;&nbsp;
                <strong class="text-primary">&lt;=</strong>
                n1
            </td>
            <td>Numerically less than [or equal to] n1.</td>
        </tr>
        <tr>
            <td>
                <strong class="text-primary">&gt;</strong>
                n1&nbsp;&nbsp;&nbsp;&nbsp;
                <strong class="text-primary">&gt;=</strong>
                n1
            </td>
            <td>Numerically greater than [or equal to] n1.</td>
        </tr>
        <tr>
            <td>
                <strong class="text-primary">=</strong> n1
            </td>
            <td>Numerically equal to n1.</td>
        </tr>
        <tr>
            <td>
                <strong class="text-success">!</strong> filter
            </td>
            <td>Invert any filter listed above with an exclamation
                mark.</td>
        </tr>
        <tr>
            <td>
                filter1
                <strong class="text-success">|</strong>
                filter2
            </td>
            <td>The value must match at least one of the combined
                filters.</td>
        </tr>
        <tr>
            <td>
                filter1
                <strong class="text-success">&</strong>
                filter2
            </td>
            <td>The value must match all of the combined filters.</td>
        </tr>
    </tbody>
</table>

For instance, the filters could be used and combined in the following ways:

<table class="table table-condensed">
    <thead>
        <tr>
            <th class="col-md-3 col-xs-4">Examples</th>
            <th class="col-md-9 col-xs-8"></th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>
                <strong class="text-primary">&lt;</strong>
                400
                <strong class="text-success">&</strong>
                <strong class="text-success">!</strong><strong class="text-danger">""</strong>
            </td>
            <td>
                Numerically less than 400 and not blank.
            </td>
        </tr>
        <tr>
            <td>
                <strong class="text-primary">=</strong>100
                <strong class="text-success">|</strong>
                <strong class="text-primary">=</strong>200
                <strong class="text-success">|</strong>
                <strong class="text-primary">=</strong>300
            </td>
            <td>
                Numerically equal to either 100, 200 or 300.
            </td>
        </tr>
        <tr>
            <td>
                gold
                <strong class="text-success">& !</strong>
                fool's
                <strong class="text-success">& !</strong>
                pyrite
            </td>
            <td>
                Contains the word <strong>gold</strong>, but not <strong>fool's</strong> or <strong>pyrite</strong>.
            </td>
        </tr>
        <tr>
            <td>
                <strong class="text-danger">/</strong>^[A-Z][0-9]+$<strong class="text-danger">/</strong>
            </td>
            <td>
                A letter followed by one or more numbers (MySQL).
            </td>
        </tr>
        <tr>
            <td>
                <strong class="text-success">!</strong>
                <strong class="text-danger">/</strong>[A-Z]<strong class="text-danger">/</strong>
            </td>
            <td>
                Does not contain any letters (MySQL).
            </td>
        </tr>
        <tr>
            <td>
                <abbr title="Numerically between 1 and 100">1
                <strong class="text-primary">to</strong>
                100</abbr>
                <abbr title="and"><strong class="text-success">&</strong></abbr>
                <abbr title="does not contain a period">
                <strong class="text-success">!</strong>
                .</abbr>
                <abbr title="and"><strong class="text-success">&</strong></abbr>
                <abbr title="ends with 0, 2, 4, 6 or 8."><strong class="text-danger">/</strong>[02468]$<strong class="text-danger">/</strong></abbr>
            </td>
            <td>
                Even integers between 1 and 100.
            </td>
        </tr>
    </tbody>
</table>

Note that with most delimiters the whitespace is optional, so <strong class="text-primary">&lt;</strong>400<strong class="text-success">&</strong><strong class="text-success">!</strong><strong class="text-danger">""</strong> and 
<strong class="text-primary">&lt;</strong> 400 <strong class="text-success">&</strong> <strong class="text-success">!</strong> <strong class="text-danger">""</strong> are equivalent.

[View Full Guide](http://www.kappasoft.net/extensions/yii/advanced-filters)

[Contact Author](http://www.kappasoft.net/contact)
