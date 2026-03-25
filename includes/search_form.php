<div>
    <button type="button" onclick="switchTab('flights')" id="tab-flights">Flights</button>
    <button type="button" onclick="switchTab('hotels')" id="tab-hotels">Hotels</button>
    <button type="button" onclick="switchTab('packages')" id="tab-packages">Packages</button>
</div>
<div id="form-flights">
    <form action="search_results.php" method="GET">
        <input type="hidden" name="type" value="flights">

        <div>
            <label>
                <input type="radio" name="trip_type" value="return" checked onchange="toggleReturnDate(this)"> Return
            </label>
            <label>
                <input type="radio" name="trip_type" value="one_way" onchange="toggleReturnDate(this)"> One Way
            </label>
        </div>

        <div>
            <label for="from">From</label>
            <input type="text" id="from" name="from" placeholder="City or airport">
        </div>

        <div>
            <label for="to">To</label>
            <input type="text" id="to" name="to" placeholder="City or airport">
        </div>

        <div>
            <label for="depart">Depart</label>
            <input type="date" id="depart" name="depart">
        </div>

        <div id="return_date_group">
            <label for="return">Return</label>
            <input type="date" id="return" name="return">
        </div>

        <div>
            <label for="passengers">Passengers</label>
            <input type="number" id="passengers" name="passengers" value="1" min="1">
        </div>

        <div>
            <button type="submit">Search</button>
        </div>
    </form>
</div>

<div id="form-hotels" style="display:none;">
    <form action="search_results.php" method="GET">
        <input type="hidden" name="type" value="hotels">

        <div>
            <label for="hotel_destination">Destination</label>
            <input type="text" id="hotel_destination" name="destination" placeholder="Hotel, region or city">
        </div>

        <div>
            <label for="check_in">Check In</label>
            <input type="date" id="check_in" name="check_in">
        </div>

        <div>
            <label for="check_out">Check Out</label>
            <input type="date" id="check_out" name="check_out">
        </div>

        <div>
            <label for="guests">Guests</label>
            <input type="number" id="guests" name="guests" value="1" min="1">
        </div>

        <div> 
            <button type="submit">Search</button>
        </div>
    </form>
</div>

<div id="form-packages" style="display:none;">
    <form action="search_results.php" method="GET">
        <input type="hidden" name="type" value="packages">

        <div>
            <label for="pkg_from">From</label>
            <input type="text" id="pkg_from" name="from" placeholder="Departing from">
        </div>

        <div>
            <label for="pkg_to">To</label>
            <input type="text" id="pkg_to" name="to" placeholder="Destination">
        </div>

        <div>
            <label for="pkg_when">When</label>
            <input type="date" id="pkg_when" name="when">
        </div>

        <div>
            <label for="duration">Duration</label>
            <input type="number" id="duration" name="duration" value="7" min="1"> nights
        </div>

        <div>
            <label for="people">Number of People</label>
            <input type="number" id="people" name="people" value="1" min="1">
        </div>

        <div>
            <button type="submit">Search</button>
        </div>
    </form>
</div>

<script>
    function switchTab(tab) {
        ['flights', 'hotels', 'packages'].forEach(function(t) {
            document.getElementById('form-' + t).style.display = t === tab ? '' : 'none';
        });
    }

    function toggleReturnDate(radio) {
        document.getElementById('return_date_group').style.display =
            radio.value === 'one_way' ? 'none' : '';
    }
</script>
