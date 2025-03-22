# Breakdance-Word-Counter-Plugin
Create post meta that holds the word count and read time for any post or page.

Through an admin page:
You can change the Words Per Minute (default is 238, the average read speed).
Enable Automate - which will automatically count the words and add the meta for any Breakdance post when saving.
Enter Post ID - to process one post.
Select a post-type - to process all posts of that type.

Meta is saved to the post ID, in the post-meta table.
_bd_word_count = count of words
_bd_read_time = Minutes to read (based on setting above)

CAVEAT
Breakdance saves most text (for text and headings, but also many other elements) within a LONG JSON string, as a "text" entry. This plugin counts the words in those entries.
This will include button text and text in other elements, like image captions. It may not be an exact representation of actual 'text to be read' - but should remain fairly close.
There's no guarantee, however, that all text in all elements is stored this way and will be counted.
