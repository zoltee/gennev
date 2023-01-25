import classNames from "classnames";
import "./Pagination.css";

export default function Pagination({pagination, setPage}) {
   if (!pagination.need_pagination){
        return;
    }
   let pages = [];
    for(let i=pagination.pages_start;i<=pagination.pages_end;i++ ){
        pages.push(<li className={classNames("page-item", {active: pagination.current_page === i})} onClick={() => setPage(i)} key={i}>
                    <span className="page-link">{i}</span>
                </li>);
    }

    return (
        <ul className="pagination">
            <li className={classNames('page-item', {disabled: !pagination.need_first})} onClick={() => setPage(pagination.first_page)}>
                <span className="page-link" aria-label="First">&laquo;</span>
            </li>
            <li className={classNames('page-item', {disabled: !pagination.need_prev})} onClick={() => setPage(pagination.prev_page)}>
                <span className="page-link" aria-label="Previous">&lsaquo;</span>
            </li>
            {pages}
            <li className={classNames('page-item', {disabled: !pagination.need_next})} onClick={() => setPage(pagination.next_page)}>
                <span className="page-link" aria-label="Next">&rsaquo;</span>
            </li>
            <li className={classNames('page-item', {disabled: !pagination.need_last})} onClick={() => setPage(pagination.last_page)} title={`Total: ${pagination.total}, ${pagination.pages} Pages`}>
                <span className="page-link" aria-label="Last">&raquo;</span>
            </li>

        </ul>
    );
}

