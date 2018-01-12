<div class="col-md-9">
    <article role="article">
        <h1>$Title</h1>

        <% if $Content %>
            <div class="typography">$Content</div>
        <% end_if %>
    </article>

    <form $SearchForm.FormAttributes class="header-search" role="search">
        <div class="form-group">
            <input name="Search" aria-label="search" type="text" class="form-control" placeholder="Search...">
        </div>
        <button type="submit" class="btn btn-primary">Search</button>
    </form>

</div>